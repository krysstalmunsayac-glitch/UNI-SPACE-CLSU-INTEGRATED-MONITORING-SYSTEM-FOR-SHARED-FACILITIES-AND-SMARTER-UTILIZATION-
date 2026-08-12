@props(['value' => null])
<option value="{{ $value }}" {{ $attributes->except(['value']) }}>{{ $slot }}</option>
