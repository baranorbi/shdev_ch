<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
            'date' => is_string($this->input('date')) ? trim($this->input('date')) : $this->input('date'),
            'hour' => is_string($this->input('hour')) ? trim($this->input('hour')) : $this->input('hour'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'date' => 'required|date|after_or_equal:today',
            'hour' => 'required|date_format:H:i',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('date') || $validator->errors()->has('hour')) {
                return;
            }

            $scheduledAt = $this->scheduledAt();

            if (! $scheduledAt) {
                $validator->errors()->add('hour', 'Please select a valid appointment time.');

                return;
            }

            if ($scheduledAt->isWeekend()) {
                $validator->errors()->add('date', 'Bookings are not available on weekends.');
            }

            if ($scheduledAt->hour < 8 || $scheduledAt->hour >= 17) {
                $validator->errors()->add('hour', 'Bookings are only available between 8:00 AM and 5:00 PM.');
            }

            if (Booking::where('scheduled_at', $scheduledAt)->exists()) {
                $validator->errors()->add('hour', 'This time slot is already booked. Please choose another time.');
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'date.required' => 'Please select a date.',
            'date.after_or_equal' => 'Please select a date from today onwards.',
            'hour.required' => 'Please select a time.',
            'hour.date_format' => 'Please select a valid time in HH:MM format.',
        ];
    }

    private function scheduledAt(): ?Carbon
    {
        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                $this->input('date').' '.$this->input('hour')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
