@extends('layouts.auth')

@section('title', 'Contact | Elevanix')

@section('content')
<x-public.shell
    title="Contact Elevanix"
    description="Send a message about Elevanix access, registration, or platform questions."
>
    <form method="POST" action="{{ route('contact.submit') }}" class="row g-3" data-loading-form>
        @csrf
        <div class="col-md-6">
            <label class="form-label" for="name">Name <span class="required-mark">*</span></label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email <span class="required-mark">*</span></label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="message">Message <span class="required-mark">*</span></label>
            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-actions">
            <a class="btn btn-outline-secondary" href="{{ route('home') }}">Back Home</a>
            <button class="btn btn-primary" type="submit" data-loading-text="Sending...">
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                Send Message
            </button>
        </div>
    </form>
</x-public.shell>
@endsection
