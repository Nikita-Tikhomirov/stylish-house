<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'message' => 'nullable|string|max:255',
            'privacy_consent' => 'accepted',
        ]);

        $data['user_message'] = $data['message'] ?? '';
        unset($data['message'], $data['privacy_consent']);

        try {
            Mail::send('emails.contact', $data, function ($m) {
                $m->to('info@stylish-house.net')->subject('Новое сообщение с сайта');
            });

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            \Log::error('Mail error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Не удалось отправить сообщение. Попробуйте позднее.',
            ], 500);
        }
    }
}
