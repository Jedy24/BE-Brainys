<?php

namespace App\Services;

use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    private $telegram;
    private $telegramChatId;
    private $discordBotToken;

    public function __construct()
    {
        $this->telegram         = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->telegramChatId   = env('TELEGRAM_CHAT_ID');
        $this->discordBotToken  = env('DISCORD_BOT_TOKEN');
    }

    /**
     * Kirim laporan ke Telegram (bisa dengan atau tanpa file)
     *
     * @param string|null $filePath Path file yang akan dikirim (opsional)
     * @param string|null $fileName Nama file yang akan dikirim (opsional)
     * @param string $message Pesan yang akan dikirim
     */
    public function sendToTelegram(?string $filePath, ?string $fileName, string $message)
    {
        try {
            $params = [
                'chat_id' => $this->telegramChatId,
                'caption' => $message,
                'parse_mode' => 'Markdown',
            ];

            if ($filePath && $fileName && Storage::exists($filePath)) {
                $params['document'] = InputFile::create(Storage::path($filePath), $fileName);
                $this->telegram->sendDocument($params);
            } else {
                $this->telegram->sendMessage([
                    'chat_id' => $this->telegramChatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram report: " . $e->getMessage());
        }
    }

    /**
     * Kirim laporan ke Discord (bisa dengan atau tanpa file)
     *
     * @param string|null $filePath Path file yang akan dikirim (opsional)
     * @param string|null $fileName Nama file yang akan dikirim (opsional)
     * @param string $message Pesan yang akan dikirim
     * @param string $channelId ID channel Discord tujuan
     */
    public function sendToDiscordChannel(?string $filePath, ?string $fileName, string $message, string $channelId)
    {
        if (!$this->discordBotToken) {
            Log::warning("Discord bot token not configured.");
            return;
        }

        $discordApiUrl = "https://discord.com/api/v10/channels/{$channelId}/messages";

        try {
            $request = Http::withHeaders([
                'Authorization' => "Bot {$this->discordBotToken}",
            ]);

            if ($filePath && $fileName && Storage::exists($filePath)) {
                $file = fopen(Storage::path($filePath), 'r');
                $request = $request->attach('file', $file, $fileName);
            }

            $response = $request->post($discordApiUrl, [
                'content' => $message,
            ]);

            if (!$response->successful()) {
                Log::error("Discord API request failed: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Discord report: " . $e->getMessage());
        }
    }
}
