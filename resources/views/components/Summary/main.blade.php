@extends('main')
@section('css')
    @includeWhen( request()->path() === 'case-summary' ,'components.Summary.css')
@endsection

@section('content')
    <div>
        @livewire('case-summary')
    </div>
@endsection

@push('scripts')
    @includeWhen( request()->path() === 'case-summary' ,'components.Summary.js')
@endpush
