@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <img src="{{ url('img/Logo@2x.png') }}" class="logo" alt="Brainys">
            @else
                <img src="https://res.cloudinary.com/dcwpj7pny/image/upload/v1716910098/Logo_2x_a1qudn.png" width="300" alt="Brainys">
            @endif
        </a>
    </td>
</tr>
