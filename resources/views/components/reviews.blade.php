@props(['model', 'type', 'reviews', 'ownerId' => null])
@php $canReview = auth()->check() && auth()->id() !== $ownerId && ! $model->reviews()->where('user_id', auth()->id())->exists(); @endphp
<div class="panel mb-4" id="avis">
  <div class="panel-head"><h2 class="h4 mb-0">{{ __('Avis') }} ({{ $model->ratingCount() }})</h2>@if ($model->ratingCount())<span><x-stars :value="$model->ratingAverage()" /> <b class="tnum">{{ number_format($model->ratingAverage(), 1, ',', '') }}</b></span>@endif</div>
  @foreach ($reviews as $v)
    <div class="d-flex gap-3 py-3 border-bottom">
      <x-avatar :user="$v->user" :size="38" />
      <div class="flex-grow-1 min-w-0">
        <div class="d-flex justify-content-between gap-2 flex-wrap"><b>{{ $v->user->name }}</b><span class="small muted">{{ date_human($v->created_at, 'D MMM YYYY') }}</span></div>
        <x-stars :value="$v->rating" />
        <p class="mb-0">{{ $v->body }}</p>
        @if ($v->reply)<div class="review-reply mt-2"><b class="small">{{ __('Réponse du professionnel') }}</b><p class="mb-0 small">{{ $v->reply }}</p></div>
        @elseif (auth()->id() && auth()->id() === $ownerId)
          <form method="post" action="{{ route('reviews.reply', $v) }}" class="mt-2 d-flex gap-2">@csrf<input name="reply" class="form-control form-control-sm" required maxlength="1500" placeholder="{{ __('Répondre publiquement…') }}"><button class="btn btn-soft btn-sm">{{ __('Publier') }}</button></form>
        @endif
      </div>
    </div>
  @endforeach
  @if ($reviews->isEmpty())<p class="muted mb-0">{{ __('Soyez le premier à partager votre expérience.') }}</p>@endif
  @if ($canReview)
    <form method="post" action="{{ route('reviews.store', [$type, $model->id]) }}" class="mt-4 review-form">@csrf
      <h3 class="h6">{{ __('Donner mon avis') }}</h3>
      <div class="rating-input mb-2" role="radiogroup" aria-label="{{ __('Note') }}">@for ($i = 5; $i >= 1; $i--)<input type="radio" name="rating" id="rt{{ $i }}" value="{{ $i }}" required @checked(old('rating') == $i)><label for="rt{{ $i }}" title="{{ $i }}/5"><i class="bi bi-star-fill"></i></label>@endfor</div>
      <textarea name="body" class="form-control mb-2" rows="3" required minlength="10" maxlength="2000" placeholder="{{ __('Racontez votre expérience (10 caractères minimum)') }}">{{ old('body') }}</textarea>
      <button class="btn btn-brand btn-sm">{{ __('Publier mon avis') }}</button>
    </form>
  @elseif (! auth()->check())
    <p class="small mt-3 mb-0"><a href="{{ route('login') }}">{{ __('Connectez-vous') }}</a> {{ __('pour donner votre avis.') }}</p>
  @endif
</div>
