<?php

namespace App\Console\Commands;

use App\Models\Party;
use Illuminate\Console\Command;

class ClearPartiesCommand extends Command
{
    protected $signature = 'parties:clear
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete all parties and their data (markets, bets, members, invitations).';

    public function handle(): int
    {
        $count = Party::count();

        if ($count === 0) {
            $this->info('No parties to clear.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("This will permanently delete {$count} party(ies) and all their markets, bets, and members. Continue?", false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        Party::query()->delete();
        $this->info("Cleared {$count} party(ies).");

        return self::SUCCESS;
    }
}
