<?php

namespace App\Livewire;

use Livewire\Component;
use TomatoPHP\FilamentMediaManager\Models\Media;

class Picker extends Component
{
    public $showModal = false;
    public $selectedImageUrl;
    public array $images = [];

    protected $listeners = ['openMediaPicker' => 'openModal'];

    public function openModal()
    {
        $this->images = Media::whereIn('mime_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->latest()
            ->get()
            ->toArray();
        $this->showModal = true;
    }

    public function selectImage($imageUrl)
    {
        $this->dispatch('insert-image', url: $imageUrl);
        $this->showModal = false; // Bezárjuk a modált
    }

    public function render()
    {
        return view('livewire.picker', [
            'images' => Media::latest()->get()
        ]);
    }
}
