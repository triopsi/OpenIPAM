<x-filament::avatar
    :src="filament()->getUserAvatarUrl(filament()->auth()->user())"
    alt=""
    size="sm"
/>
