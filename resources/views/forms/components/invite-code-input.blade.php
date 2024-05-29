<div class="flex items-center space-x-2">
    <input type="text" wire:model.defer="{{ $getStatePath() }}" {{ $attributes->merge($getExtraInputAttributes()) }}
        class="form-input w-full" />
    <button type="button" wire:click="generateInviteCode" class="btn btn-primary">
        Refresh
    </button>
</div>
