<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use Dompdf\Dompdf;
use Illuminate\Contracts\View\Factory as Views;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class QueueWorksheetPrint
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly Views $views,
    ) {}

    /** Whether the Mac Studio's print folder is mounted and writable. */
    public function available(): bool
    {
        $spool = (string) config('dashboard.homework_print_spool');

        return $spool !== '' && $this->files->isDirectory($spool) && $this->files->isWritable($spool);
    }

    public function handle(string $job, array $kid, string $date, array $tasks): void
    {
        $spool = (string) config('dashboard.homework_print_spool');
        if (! $this->available()) {
            throw ValidationException::withMessages(['print' => 'The Mac Studio print queue is unavailable.']);
        }

        Cache::lock('homework:print:'.$job, 30)->block(5, fn () => $this->queue($spool, $job, $kid, $date, $tasks));
    }

    private function queue(string $spool, string $job, array $kid, string $date, array $tasks): void
    {
        $temp = $spool.'/'.$job.'.tmp';
        if ($this->files->exists($temp) || $this->state($job) !== 'missing') {
            return;
        }

        $pdf = new Dompdf;
        $pdf->setPaper('letter');
        $pdf->loadHtml($this->views->make('modules.homework.print', compact('kid', 'date', 'tasks'))->render(), 'UTF-8');
        $pdf->render();

        if ($this->files->put($temp, $pdf->output(), lock: true) === false || ! $this->files->move($temp, $spool.'/'.$job.'.pdf')) {
            $this->files->delete($temp);
            throw ValidationException::withMessages(['print' => 'The worksheet packet could not be sent to the Mac Studio.']);
        }
    }

    public function state(string $job): string
    {
        $spool = (string) config('dashboard.homework_print_spool');
        if ($spool === '' || ! $this->files->isDirectory($spool)) {
            return 'missing';
        }

        $base = $spool.'/'.$job;

        foreach (['sent', 'failed'] as $state) {
            if ($this->files->exists($base.'.'.$state)) {
                return $state;
            }
        }

        return $this->files->exists($base.'.pdf') || $this->files->exists($base.'.working') ? 'queued' : 'missing';
    }
}
