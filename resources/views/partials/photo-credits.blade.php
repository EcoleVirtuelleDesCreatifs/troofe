<div class="wrap gutter pb-3">
  <details class="small muted">
    <summary>{{ __('Photos d’illustration et crédits') }}</summary>
    <p class="mt-2">{{ __('Les photographies d’illustration ne représentent pas nécessairement les établissements, personnes, événements ou produits référencés. Les médias fournis par les professionnels restent prioritaires.') }}</p>
    <p>{{ __('Les photos sont redimensionnées et peuvent être recadrées à l’affichage. Les licences indiquées restent applicables aux photographies correspondantes.') }}</p>
    <p>{{ __('Certaines photographies proviennent de la galerie fournie pour le site. Leurs versions Web sont compressées ; les fichiers originaux sont conservés sans modification.') }}</p>
    <ul>
      @foreach (\App\Support\PhotoLibrary::PEXELS as [$slug, $id, $author])
        <li><a class="d-inline" href="https://www.pexels.com/photo/{{ $slug }}-{{ $id }}/" target="_blank" rel="noopener">{{ $author }} — Pexels</a></li>
      @endforeach
      @foreach (\App\Support\PhotoLibrary::COMMONS as [$file, $author, $license])
        <li><a class="d-inline" href="https://commons.wikimedia.org/wiki/File:{{ rawurlencode($file) }}" target="_blank" rel="noopener">{{ $author }} — {{ str_replace('_', ' ', $file) }}</a> · @if ($license === 'CC0')<a class="d-inline" href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" rel="noopener">CC0</a>@else<a class="d-inline" href="https://creativecommons.org/licenses/by-sa/{{ $license }}/" target="_blank" rel="noopener">CC BY-SA {{ $license }}</a>@endif</li>
      @endforeach
    </ul>
  </details>
</div>
