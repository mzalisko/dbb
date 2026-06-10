<?php

namespace Nwdb\Console;

use Illuminate\Console\Command;
use Nwdb\WpFeed\Cipher;

class MakeSigningKey extends Command
{
    protected $signature = 'nwdb:make-signing-key';

    protected $description = 'Згенерувати центральну підписну пару Ed25519 для dead-drop фідів (вставити у .env)';

    public function handle(): int
    {
        $pair = Cipher::generateSigningKeypair();

        $this->line('Додайте у .env (секрет не комітити і не логувати):');
        $this->newLine();
        $this->line('NWDB_SIGN_PUBLIC='.base64_encode($pair['public']));
        $this->line('NWDB_SIGN_SECRET='.base64_encode($pair['secret']));

        return self::SUCCESS;
    }
}
