import { TextInput } from '@filament/forms';
import { nanoid } from 'nanoid';

export default TextInput.extend({
    methods: {
        generateInviteCode() {
            this.value = nanoid(8);
        },
    },
    render() {
        return (
            <div class="flex items-center space-x-2">
                <input
                    type="text"
                    value={this.value}
                    onInput={(e) => (this.value = e.target.value)}
                    class="form-input w-full"
                />
                <button
                    type="button"
                    onClick={this.generateInviteCode}
                    class="btn btn-primary"
                >
                    Refresh
                </button>
            </div>
        );
    },
});
