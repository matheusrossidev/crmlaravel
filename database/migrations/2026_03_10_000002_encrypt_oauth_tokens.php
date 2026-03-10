<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypts existing plaintext OAuth tokens in the database.
 * After this migration, the OAuthConnection model uses 'encrypted' cast.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('oauth_connections')->get(['id', 'access_token', 'refresh_token']);

        foreach ($rows as $row) {
            $update = [];

            // Only encrypt if the value is not already encrypted (doesn't start with eyJ)
            if ($row->access_token && ! $this->isEncrypted($row->access_token)) {
                $update['access_token'] = Crypt::encryptString($row->access_token);
            }

            if ($row->refresh_token && ! $this->isEncrypted($row->refresh_token)) {
                $update['refresh_token'] = Crypt::encryptString($row->refresh_token);
            }

            if ($update) {
                DB::table('oauth_connections')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Decrypt tokens back to plaintext
        $rows = DB::table('oauth_connections')->get(['id', 'access_token', 'refresh_token']);

        foreach ($rows as $row) {
            $update = [];

            if ($row->access_token && $this->isEncrypted($row->access_token)) {
                try {
                    $update['access_token'] = Crypt::decryptString($row->access_token);
                } catch (\Exception) {
                    // Skip if already plaintext or corrupted
                }
            }

            if ($row->refresh_token && $this->isEncrypted($row->refresh_token)) {
                try {
                    $update['refresh_token'] = Crypt::decryptString($row->refresh_token);
                } catch (\Exception) {
                    // Skip if already plaintext or corrupted
                }
            }

            if ($update) {
                DB::table('oauth_connections')->where('id', $row->id)->update($update);
            }
        }
    }

    private function isEncrypted(string $value): bool
    {
        // Laravel encrypted strings start with 'eyJ' (base64 of '{"')
        return str_starts_with($value, 'eyJ');
    }
};
