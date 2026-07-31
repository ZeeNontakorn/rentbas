<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrivateScheduleController extends Controller
{
    public function index(Request $request)
    {
        $coaches = User::query()
            ->where('role', 'staff')
            ->where('membership_type', 'coach')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedCoachId = $request->query('coach_id') === 'all'
            ? 'all'
            : ($coaches->contains('id', (int) $request->query('coach_id'))
                ? (int) $request->query('coach_id')
                : 'all');

        return view('admin.private-training.schedule', compact('coaches', 'selectedCoachId'));
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'coach_id' => ['required', 'string'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $showAll = $data['coach_id'] === 'all';
        $coach = $showAll ? null : $this->coach((int) $data['coach_id']);
        $coachIds = $showAll
            ? User::where('role', 'staff')->where('membership_type', 'coach')->pluck('id')
            : collect([$coach->id]);
        $from = Carbon::parse($data['start'])->toDateString();
        $until = Carbon::parse($data['end'])->toDateString();

        $availabilities = Availability::with('user')
            ->whereIn('user_id', $coachIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->get()
            ->map(fn (Availability $slot) => [
                'id' => 'availability-'.$slot->id,
                'title' => ($showAll ? $slot->user->name.' · ' : '').($slot->status === 'available'
                    ? ($slot->detail ?: 'เปิดรับจอง')
                    : ($slot->detail ?: 'ไม่ว่าง')),
                'start' => $slot->date.'T'.substr($slot->start_time, 0, 8),
                'end' => $slot->date.'T'.substr($slot->end_time, 0, 8),
                'backgroundColor' => $slot->status === 'available' ? '#16a34a' : '#64748b',
                'borderColor' => $slot->status === 'available' ? '#15803d' : '#475569',
                'editable' => true,
                'extendedProps' => [
                    'kind' => 'availability',
                    'recordId' => $slot->id,
                    'status' => $slot->status,
                    'detail' => $slot->detail,
                    'coachId' => $slot->user_id,
                    'coachName' => $slot->user->name,
                ],
            ]);

        $bookings = PrivateTrainingBooking::with(['user', 'coach'])
            ->whereIn('coach_id', $coachIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->map(fn (PrivateTrainingBooking $booking) => [
                'id' => 'booking-'.$booking->id,
                'title' => ($showAll ? $booking->coach->name.' · ' : '').'Private: '.$booking->user->name,
                'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                'backgroundColor' => $booking->status === 'confirmed' ? '#7c3aed' : '#f97316',
                'borderColor' => $booking->status === 'confirmed' ? '#6d28d9' : '#ea580c',
                'editable' => false,
                'extendedProps' => [
                    'kind' => 'booking',
                    'status' => $booking->status,
                    'coachId' => $booking->coach_id,
                    'coachName' => $booking->coach->name,
                ],
            ]);

        return response()->json($availabilities->concat($bookings)->values());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $coach = $this->coach((int) $data['coach_id']);
        $slot = $coach->availabilities()->create($this->slotData($data));

        return response()->json(['id' => $slot->id], 201);
    }

    public function update(Request $request, Availability $availability)
    {
        $data = $this->validated($request);
        $coach = $this->coach((int) $data['coach_id']);
        abort_unless($availability->user_id === $coach->id, 404);
        $availability->update($this->slotData($data));

        return response()->json(['id' => $availability->id]);
    }

    public function destroy(Request $request, Availability $availability)
    {
        $coachId = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:users,id'],
        ])['coach_id'];
        $coach = $this->coach((int) $coachId);
        abort_unless($availability->user_id === $coach->id, 404);
        $availability->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'coach_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['available', 'booked'])],
            'detail' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function slotData(array $data): array
    {
        return [
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'detail' => $data['detail'] ?? null,
        ];
    }

    private function coach(int $id): User
    {
        return User::query()
            ->whereKey($id)
            ->where('role', 'staff')
            ->where('membership_type', 'coach')
            ->firstOrFail();
    }
}
