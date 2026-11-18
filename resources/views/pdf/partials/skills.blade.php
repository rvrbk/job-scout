@if (count($skills))
    <h2>{{ __('Skills') }}</h2>
    <div>
        @foreach ($skills as $skill)<span class="chip">{{ $skill }}</span> @endforeach
    </div>
@endif
