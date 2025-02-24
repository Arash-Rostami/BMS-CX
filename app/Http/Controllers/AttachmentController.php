<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;


class AttachmentController extends Controller
{
    public function serve(string $path)
    {
        $fullPath = public_path('attachments/' . $path);

        abort_unless(File::exists($fullPath), 404);

        return response()->file($fullPath);
    }
}
