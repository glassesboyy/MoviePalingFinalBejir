<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Seats;
use App\Models\service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function list() {
        try {
            $bookings = Booking::with(['schedule.film', 'bookingseat', 'bookingservice'])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'status' => true,
                'message' => 'List Booking',
                'data' => $bookings
            ]);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()]);
        }
    }
    public function index($scheduleId)
    {
        try {
            $schedule = Schedule::findorfail($scheduleId);
            $film = $schedule->film;
            $availableSchedules = Schedule::where('films_id', $film->id)->get();
            $availableSeats = Seats::where('schedule_id', $scheduleId)
                ->select([
                    'id',
                    'seat_number',
                    'status'
                ])
                ->get();
            $service = service::all();

            return response()->json([
                'status' => true,
                'message' => 'Booking List',
                'data' => [
                    'schedule' => $schedule,
                    'film' => $film,
                    'availableSchedules' => $availableSchedules,
                    'availableSeats' => $availableSeats,
                    'service' => $service,
                ]
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $userId = $request->user_id ?? Auth::id();

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:schedules,id',
            'seat_id' => 'required|array', // Pastikan seat_id adalah array
            'seat_id.*' => 'exists:seats,id', // Validasi setiap ID kursi
            'services' => 'array',
            'services.*' => 'exists:services,id',
            'user_id' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $schedule = Schedule::findOrFail($request->schedule_id);

            $seats = Seats::whereIn('id', $request->seat_id)->get();

            foreach ($seats as $seat) {
                if ($seat->status != 'sedia') {
                    return response()->json(['status' => false, 'message' => 'Chair Has Been Chosen!'], 400);
                }
            }

            $totalPrice = $schedule->price * count($seats);

            $booking = Booking::create([
                'user_id' => $userId,
                'schedule_id' => $schedule->id,
                'total_price' => $totalPrice,
                'status' => 'pending'
            ]);

            $booking->bookingseat()->attach($seats);

            $seats->each(function ($seat) {
                $seat->update(['status' => 'tidak tersedia']);
            });

            if ($request->has('services')) {
                $services = Service::findMany($request->services);
                foreach ($services as $service) {
                    $booking->bookingservice()->attach($service->id, ['jumlah' => 1]);
                    $totalPrice += $service->price;
                }
            }

            $booking->update(['total_price' => $totalPrice]);
            return response()->json(['status' => true, 'data' => $booking, 'message' => 'Successfully Booking'], 201);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 500);
        }
    }

    public function konfirmasi($scheduleId)
    {
        try {
            $booking = Booking::where('user_id', Auth::id())->where('schedule_id', $scheduleId)->latest()->first();

            if (!$booking) {
                return response()->json(['status' => false, 'message' => 'Booking Not Found'], 404);
            }

            $schedule = Schedule::with('film')->findOrFail($booking->schedule_id);
            $seat = $booking->bookingseat;
            $totalPrice = $booking->total_price;

            return response()->json([
                'status' => true,
                'data' => [
                    'booking' => $booking,
                    'schedule' => $schedule,
                    'seat' => $seat,
                    'totalPrice' => $totalPrice
                ]
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            // Temukan pemesanan berdasarkan ID
            $booking = Booking::findOrFail($id);

            // Kembalikan respons dengan data pemesanan
            return response()->json([
                'status' => true,
                'data' => $booking,
            ], 200);
        } catch (\Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Pemesanan tidak ditemukan',
            ], 404);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $scheduleDate = $booking->Schedule->date;

            if ($scheduleDate > now()->toDateString()) {
                return response()->json([
                    'status' => false,
                    'message' => 'The Order Cannot Be Edit'
                ], 400);
            }

            $request->validate([
                'seat_id' => 'required|array',
                'seat_id.*' => 'exists:seats,id',
                'services' => 'array',
                'services.*' => 'exists:services,id',
            ]);

            $booking->bookingseat->each(function ($seat) {
                $seat->update(['status' => 'sedia']);
            });

            $booking->bookingseat()->detach();

            $seats = Seats::whereIn('id', $request->seat_id)->get();
            foreach ($seats as $item) {
                $item->update(['status' => 'tidak tersedia']);
            }

            $booking->bookingseat()->attach($seats);

            if ($request->has('services')) {
                $booking->bookingservice()->detach();
                foreach ($request->services as $item) {
                    $booking->bookingservice()->attach($item, ['jumlah' => 1]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Edit Booking',
                'data' => $booking
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $scheduleDate = $booking->schedule->date;

            if ($scheduleDate > now()->toDateString()) {
                return response()->json(['status' => false, 'message' => 'Booking Cannot Be Deleted'], 400);
            }

            $booking->bookingseat->each(function ($seat) {
                $seat->update(['status' => 'sedia']);
            });

            $booking->bookingseat()->detach();
            $booking->bookingservice()->detach();
            $booking->delete();
            return response()->json(['status' => true, 'message' => 'Delete Booking'], 204);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()]);
        }
    }
}
