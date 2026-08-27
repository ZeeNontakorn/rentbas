@extends('layouts.app')
@section('title', 'เพิ่มแพ็กเกจ')
@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="mx-auto max-w-3xl px-4">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">เพิ่มแพ็กเกจใหม่</h1>
            <p class="font-sarabun mt-1 text-sm text-slate-500">กรอกรายละเอียดแพ็กเกจที่ต้องการเพิ่ม</p>
        </div>
        @if($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @include('admin.packages._form')
    </div>
</div>
@endsection
