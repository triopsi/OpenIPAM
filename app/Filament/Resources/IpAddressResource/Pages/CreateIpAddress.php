<?php

namespace App\Filament\Resources\IpAddressResource\Pages;

use App\Filament\Resources\IpAddressResource;
use App\Models\IpAddress;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateIpAddress extends CreateRecord
{
    protected static string $resource = IpAddressResource::class;

    public ?string $subnet_input = null;

    public ?string $generate_mode = 'single';

    /**
     * Mutate the validated form data before creating the model.
     *
     * Called after form validation and before the record is persisted.
     * Use this to normalize, augment, remove, or transform incoming values
     * (e.g. set defaults, format attributes, resolve relations, or strip
     * sensitive/temporary keys) so the returned array is ready for creation.
     *
     * @param  array  $data  The validated form data submitted from the form.
     * @return array The modified form data to be used when creating the model.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Wenn Massen-Generierung gewählt, IPs hier nicht direkt speichern
        if (request()->input('generate_mode') === 'subnet') {
            // Wir speichern nur das Subnetz als Dummy, eigentliche Generierung erfolgt in createRecord
            $data['ip_address'] = $data['subnet'] ?? $data['ip_address'];
        }

        return $data;
    }

    /**
     * Create a new IP address resource.
     *
     * Persists the IP address using the current form state. If $another is true,
     * the user will be redirected back to the creation form to add another record;
     * otherwise the flow will continue (e.g., redirect to the list or detail view).
     *
     * @param  bool  $another  Whether to remain on the create form to add another record.
     */
    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        $mode = $data['generate_mode'] ?? 'single';
        $subnet = $data['subnet_input'] ?? $data['subnet'] ?? null;
        $start = isset($data['subnet_start']) && is_numeric($data['subnet_start']) ? (int) $data['subnet_start'] : 1;
        $count = isset($data['subnet_count']) && is_numeric($data['subnet_count']) ? (int) $data['subnet_count'] : null;

        if ($mode === 'subnet' && $subnet) {
            // Subnetz-Parsing und IP-Generierung
            $ips = $this->calculateIpRange($subnet);
            if (empty($ips)) {
                Notification::make()->danger()->title('Ungültiges Subnetz')->body('Bitte ein gültiges Subnetz angeben.')->send();

                return;
            }
            // Bereich anwenden
            $ips = array_slice($ips, $start - 1, $count);
            foreach ($ips as $ip) {
                IpAddress::firstOrCreate([
                    'ip_address' => $ip,
                ], [
                    'version' => 4,
                    'subnet' => $subnet,
                    'status' => 'available',
                ]);
            }
            Notification::make()->success()->title('IP-Adressen generiert')->body(count($ips).' IPs wurden erstellt.')->send();
            $this->redirect(static::getResource()::getUrl('index'));

            return;
        }

        parent::create($another);
    }

    /**
     * Calculate the inclusive IP address range for a given CIDR notation.
     *
     * Parses a CIDR string (for example "192.168.0.0/24") and returns the first
     * and last IP address that belong to the network. The returned range is
     * inclusive of the network and broadcast addresses.
     *
     * @param  string  $cidr  CIDR notation (e.g. "192.168.0.0/24"). Supports IPv4 addresses.
     * @return array{start:string,end:string} Array with 'start' and 'end' keys containing
     *                                        the first and last IP addresses of the range.
     *
     * @throws \InvalidArgumentException If the provided CIDR string is invalid or cannot be parsed.
     */
    protected function calculateIpRange(string $cidr): array
    {
        // Nur IPv4 CIDR
        if (! Str::contains($cidr, '/')) {
            return [];
        }
        [$base, $prefix] = explode('/', $cidr);
        if (! filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [];
        }
        $prefix = (int) $prefix;
        if ($prefix < 1 || $prefix > 32) {
            return [];
        }
        $ipLong = ip2long($base);
        $numHosts = 2 ** (32 - $prefix);
        $ips = [];
        for ($i = 1; $i < $numHosts - 1; $i++) { // skip network and broadcast
            $ips[] = long2ip($ipLong + $i);
        }

        return $ips;
    }
}
