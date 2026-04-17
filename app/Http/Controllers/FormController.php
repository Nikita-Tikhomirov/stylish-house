<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function send(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'phone' => 'required|string',
                'message' => 'max:255',
            ]);

            // Переименовываем переменную message → user_message
            $data['user_message'] = $data['message'];
            unset($data['message']);

            Mail::send('emails.contact', $data, function ($m) {
                $m->to('info@stylish-house.net')->subject('Новое сообщение с сайта');
            });

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            \Log::error('Mail error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
