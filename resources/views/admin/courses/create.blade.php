@extends('layouts.app')
@section('title', 'เพิ่มคอร์ส')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">เพิ่มคอร์สใหม่</h1>
        <p class="font-sarabun mt-1 text-sm text-slate-500">เลือกประเภทคอร์ส แล้วกรอกรายละเอียดตามที่ต้องการ</p>
    </div>@if($errors->any())<div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}
    </div>@endif @include('admin.courses._form')
</div>
@endsection