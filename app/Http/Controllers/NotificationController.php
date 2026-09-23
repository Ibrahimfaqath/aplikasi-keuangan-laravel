<?php

namespace App\Http\Controllers;

use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $reminders = (new ReminderService)->reminders((int) Auth::id());

        return response()->json([
            'count' => count($reminders),
            'items' => $reminders,
        ]);
    }
}
