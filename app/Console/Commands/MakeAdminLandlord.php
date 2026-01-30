<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeAdminLandlord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin-landlord {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a landlord to admin status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $landlord = \App\Models\Landlord::where('email', $email)->first();

        if (!$landlord) {
            $this->error("Landlord with email {$email} not found.");
            return 1;
        }

        $landlord->update(['is_admin' => true]);
        $this->info("Landlord {$email} has been promoted to admin.");
        return 0;
    }
}
