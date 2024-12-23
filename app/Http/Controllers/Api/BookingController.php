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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingController extends Controller
{
    protected function successResponse($data, $message = '', $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    protected function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    private function formatPosterUrl($posterPath)
    {
        return $posterPath ? url('api/storage/posters/' . basename($posterPath)) : null;
    }

    public function list(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $bookings = Booking::with(['schedule.film', 'bookingseat', 'bookingservice'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $bookings->getCollection()->transform(function ($booking) {
                if ($booking->schedule && $booking->schedule->film) {
                    $booking->schedule->film->poster_url = $this->formatPosterUrl($booking->schedule->film->poster);
                }
                return $booking;
            });

            return $this->successResponse($bookings, 'Bookings retrieved successfully');
        } catch (\Exception $err) {
            return $this->errorResponse($err->getMessage());
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
            'seat_id' => 'required|array',
            'seat_id.*' => 'exists:seats,id',
            'services' => 'array',
            'services.*' => 'exists:services,id',
            'user_id' => 'exists:users,id',
        ], [
            'schedule_id.required' => 'Schedule ID is required',
            'seat_id.required' => 'At least one seat must be selected',
            'seat_id.*.exists' => 'One or more selected seats are invalid'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $schedule = Schedule::findOrFail($request->schedule_id);

            $seats = Seats::whereIn('id', $request->seat_id)->get();

            foreach ($seats as $seat) {
                if ($seat->status != 'sedia') {
                    return $this->errorResponse('Chair Has Been Chosen!', 400);
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
            return $this->successResponse(
                $booking->load(['bookingseat', 'bookingservice']), 
                'Booking created successfully', 
                201
            );
        } catch (\Exception $err) {
            return $this->errorResponse($err->getMessage());
        }
    }

    public function konfirmasi($scheduleId)
    {
        try {
            $booking = Booking::where('user_id', Auth::id())->where('schedule_id', $scheduleId)->latest()->first();

            if (!$booking) {
                return $this->errorResponse('Booking Not Found', 404);
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
            $booking = Booking::with(['schedule.film', 'bookingseat', 'bookingservice'])
                ->findOrFail($id);

            return $this->successResponse($booking, 'Booking details retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Booking not found', 404);
        } catch (\Exception $err) {
            return $this->errorResponse($err->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $scheduleDate = $booking->Schedule->date;

            if ($scheduleDate > now()->toDateString()) {
                return $this->errorResponse('The Order Cannot Be Edit', 400);
            }

            $request->validate([
                'seat_id' => 'required|array',
                'seat_id.*' => 'exists:seats,id',
                'services' => 'array',
                'services.*' => 'exists:services,id',
            ]);

            // Reset seats status
            $booking->bookingseat->each(function ($seat) {
                $seat->update(['status' => 'sedia']);
            });
            $booking->bookingseat()->detach();

            // Update seats and calculate new seat price
            $seats = Seats::whereIn('id', $request->seat_id)->get();
            foreach ($seats as $item) {
                $item->update(['status' => 'tidak tersedia']);
            }
            $booking->bookingseat()->attach($seats);

            // Calculate new total price starting with seats
            $totalPrice = $booking->schedule->price * count($seats);

            // Update services and add their prices
            if ($request->has('services')) {
                $booking->bookingservice()->detach();
                $services = Service::findMany($request->services);
                foreach ($services as $service) {
                    $booking->bookingservice()->attach($service->id, ['jumlah' => 1]);
                    $totalPrice += $service->price;
                }
            }

            // Update the total price
            $booking->update(['total_price' => $totalPrice]);

            return response()->json([
                'status' => true,
                'message' => 'Edit Booking',
                'data' => $booking->load(['bookingseat', 'bookingservice'])
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
                return $this->errorResponse('Booking Cannot Be Deleted', 400);
            }

            $booking->bookingseat->each(function ($seat) {
                $seat->update(['status' => 'sedia']);
            });

            $booking->bookingseat()->detach();
            $booking->bookingservice()->detach();
            $booking->delete();
            return response()->json(['status' => true, 'message' => 'Delete Booking'], 204);
        } catch (\Exception $err) {
            return $this->errorResponse($err->getMessage());
        }
    }
}