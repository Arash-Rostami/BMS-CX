@extends('main')
@section('css')
    @includeWhen( request()->path() === 'cost-calculation' ,'components.CostCalculation.css')
@endsection

@section('content')
    <div>
        @livewire('cost-calculation.cost-calculation-form')
    </div>
@endsection

@push('scripts')
    @includeWhen( request()->path() === 'cost-calculation' ,'components.CostCalculation.js')
@endpush
