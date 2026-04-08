<div class="max-w-md mx-auto mt-10 bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <h2 class="text-2xl font-bold mb-1 text-gray-800">Subscription Plan Details</h2>
    <p class="text-sm text-gray-500 mb-6">Configure the capacity for this subscription tier.</p>

    <form action="{{ route('pay.initialize') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label for="plan_type" class="block text-sm font-medium text-gray-700 mb-1">Plan Type</label>
            <select name="plan_type" id="plan_type" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('plan_type') border-red-500 @enderror">
                <option value="">-- Choose a Plan --</option>
                <option value="basic" {{ old('plan_type') == 'basic' ? 'selected' : '' }}>Basic</option>
                <option value="premium" {{ old('plan_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                <option value="ultimate" {{ old('plan_type') == 'ultimate' ? 'selected' : '' }}>Ultimate</option>
            </select>
            @error('plan_type')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="max_slots" class="block text-sm font-medium text-gray-700 mb-1">Maximum Slots</label>
            <div class="relative">
                <input type="number" name="max_slots" id="max_slots" min="1"
                    value="{{ old('max_slots', 1) }}"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('max_slots') border-red-500 @enderror"
                    placeholder="e.g. 10">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-400 text-sm">units</span>
                </div>
            </div>
            @error('max_slots')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" 
            class="w-full bg-slate-900 text-white font-semibold py-2.5 px-4 rounded-md hover:bg-slate-800 transition duration-200 shadow-sm">
            Save Configuration
        </button>
    </form>
</div>