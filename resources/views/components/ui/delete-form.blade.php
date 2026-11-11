@props(['action' => null, 'method' => 'POST', 'confirm' => null, 'variant' => 'danger', 'label' => 'Hapus'])

<form method="POST" action="{{ $action }}" @if($confirm) onsubmit="return confirm(@js($confirm))" @endif class="inline">
    @csrf
    @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
        @method($method)
    @endif
    <button type="submit" class="btn-{{ $variant }}">{{ $label }}</button>
</form>
