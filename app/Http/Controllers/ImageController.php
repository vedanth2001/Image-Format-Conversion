<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index()
    {
        return view('image');
    }
    
public function convertImage(Request $request)
{
    // Get the uploaded image file
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

    // Save the resized/converted image to the storage directory
    \Storage::put('image/' . uniqid() . '.' . $ext, $imageStream);

    // Redirect back to the image view with a success message
    return redirect('image')->withSuccess('Image modified successfully');
}
}