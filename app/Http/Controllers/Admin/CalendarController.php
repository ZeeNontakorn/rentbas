<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseSchedule;
use App\Models\CourseTargetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CalendarController extends Controller
{

public function calendar()
{
    return view('admin.calendars.course-calendar');
}
}