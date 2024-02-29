<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ImageController extends Controller
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
            'key'    => 'YOUR_ACCESS_KEY',
            'secret' => 'YOUR_SECRET_KEY',
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

}
