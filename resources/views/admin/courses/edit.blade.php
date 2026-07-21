@extends('layouts.app')
@section('title', 'แก้ไขคอร์ส')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">แก้ไขคอร์ส</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $course->course_name }}</p>
    </div>@if($errors->any())<div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}
    </div>@endif @include('admin.courses._form')
</div>
@endsection