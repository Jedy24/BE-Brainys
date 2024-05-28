@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <img src="{{ url('img/Favicon.png') }}" class="logo" alt="Brainys">
            @else
                <img src="https://res.cloudinary.com/dcwpj7pny/image/upload/v1716910685/Favicon_csrxzl.png" width="150" alt="Brainys">
            @endif
        </a>
    </td>
</tr>
