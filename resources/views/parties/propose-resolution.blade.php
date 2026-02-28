@extends('layouts.app')

@section('title', 'Propose resolution — ' . $market->title)

@section('content')
<div class="container">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('parties.show', $party) }}" class="btn btn-outline-secondary btn-sm btn-touch">← Back to party</a>
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Propose resolution</h1>
    </div>
    <p class="text-muted mb-3"><strong>{{ $market->title }}</strong> — Submit the outcome you believe is true. The market will be paused until an admin reviews. If your proposal is denied, you will forfeit your position (lose your stake) on this market.</p>

    <div class="card shamrock-card">
        <div class="card-header shamrock-header">Proposal</div>
        <div class="card-body">
            <form method="POST" action="{{ route('parties.propose-resolution', [$party, $market]) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="winning_option_id" class="form-label">Winning outcome</label>
                    <select name="winning_option_id" id="winning_option_id" class="form-select form-select-lg @error('winning_option_id') is-invalid @enderror" required>
                        <option value="">Choose the outcome that you believe is true</option>
                        @foreach($market->options as $option)
                            <option value="{{ $option->id }}" {{ old('winning_option_id') == $option->id ? 'selected' : '' }}>
                                @if($option->image_url)<img src="{{ $option->image_url }}" alt="" class="rounded me-1" style="height:20px;width:20px;object-fit:cover">@endif
                                {{ $option->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('winning_option_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="4" required maxlength="2000" placeholder="Explain why you believe this is the correct outcome (evidence, source, etc.).">{{ old('description') }}</textarea>
                    <div class="form-text">Up to 2000 characters.</div>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="photos" class="form-label">Photos (optional)</label>
                    <input type="file" name="photos[]" id="photos" class="form-control @error('photos.*') is-invalid @enderror" accept="image/*" multiple>
                    <div class="form-text">You can select multiple images. Max 5 MB each.</div>
                    @error('photos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-shamrock btn-lg">Submit for review</button>
            </form>
        </div>
    </div>
</div>
@endsection
