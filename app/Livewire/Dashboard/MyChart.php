<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class MyChart extends Component
{
    public $labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
    public $data = [12, 19, 3, 5, 2, 3, 10];

    public function updateChartData()
    {
        $this->data = array_map(fn($value) => rand(1, 20), $this->data);

    }

    public function render()
    {
        return view('livewire.dashboard.my-chart');
    }
}
