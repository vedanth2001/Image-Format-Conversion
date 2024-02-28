<?php

namespace App\Http\test;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;


class ImageController extends test
{
    public function index()
    {
        return view('image');
    }
    
    public function convertImage(Request $request)
    {
        $image = $request->file('image');
    
        // Get the desired file extension
        $ext = $request->input('format');

        // Initialize variables for width and height
        $width = $request->input('width');
        $height = $request->input('height');

        // Initialize the Intervention Image instance
        $imageConvert = \Image::make($image->getRealPath());

        // Resize the image if width and height are provided
        if ($width && $height) {
            $imageConvert->resize($width, $height);
        }

        // Stream the image data
        $imageStream = $imageConvert->stream($ext);

        // AWS credentials
        $credentials = [
            'key'    => 'AKIAZI2LFUUM7HFYNQWE',
            'secret' => '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',
        ];

        // Initialize S3 client for the destination bucket
        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'ap-south-1', // Assuming both buckets are in the same region
            'credentials' => $credentials,
        ]);

        try {
            // Upload the resized/converted image to the destination bucket
            $s3->putObject([
                'Bucket' => 'eazydinervednth',
                'Key'    => 'Modified Images/' . uniqid() . '.' . $ext,
                'Body'   => $imageStream->__toString(),
            ]);

            // Redirect back to the image view with a success message
            return redirect('image')->withSuccess('Image modified and uploaded successfully');
        } catch (AwsException $e) {
            // Handle AWS exception
            return redirect('image')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function copyImages()
    {
        // Source bucket details
        $sourceBucketName = 'eazydinervednth';
        $sourceBucketRegion = 'ap-south-1';
        $sourceBucketPrefix = 'image/';

        // Destination bucket details
        $destBucketName = 'eazydinervednth';
        $destBucketRegion = 'ap-south-1';
        $destBucketPrefix = 'Modified Images/';

        // AWS credentials
        $credentials = [
            'key'    => 'AKIAZI2LFUUM7HFYNQWE',
            'secret' => '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',
        ];

        // Initialize S3 clients for both source and destination buckets
        $sourceS3 = new S3Client([
            'version'     => 'latest',
            'region'      => $sourceBucketRegion,
            'credentials' => $credentials,
        ]);

        $destS3 = new S3Client([
            'version'     => 'latest',
            'region'      => $destBucketRegion,
            'credentials' => $credentials,
        ]);

        try {
            // List objects in the source bucket
            $objects = $sourceS3->getPaginator('ListObjects', [
                'Bucket' => $sourceBucketName,
                'Prefix' => $sourceBucketPrefix,
            ]);

            // Iterate through objects and copy them to the destination bucket
            foreach ($objects as $result) {
                foreach ($result['Contents'] as $object) {
                    $sourceKey = $object['Key'];
                    $destKey = str_replace($sourceBucketPrefix, $destBucketPrefix, $sourceKey);

                    // Copy object from source to destination bucket
                    $destS3->copyObject([
                        'Bucket'     => $destBucketName,
                        'Key'        => $destKey,
                        'CopySource' => "{$sourceBucketName}/{$sourceKey}",
                    ]);
                }
            }

            echo "Images copied successfully from '{$sourceBucketName}' to '{$destBucketName}'";
        } catch (AwsException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
