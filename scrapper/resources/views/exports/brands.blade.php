<?xml version="1.0" encoding="utf-8"?>
<brands>
    @foreach($brands as $brand)
    <brand>
    @foreach($brand->getAttributes() as $attribute => $value)
            <{{ $attribute }}>{{ $value }}</{{ $attribute }}>
    @endforeach
    </brand>
    @endforeach
</brands>
