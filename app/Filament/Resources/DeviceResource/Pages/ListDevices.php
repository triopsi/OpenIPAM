<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use App\Models\Device;
use App\Models\IpAddress;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    /**
     * Define header actions for the device list page.
     *
     * Contains Create action and CSV Import action with comprehensive
     * mapping system for CSV columns and IP address processing.
     *
     * @return array<Actions\Action> Array of Filament actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('csvImport')
                ->label(__('devices.csv_import.title'))
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->modalWidth('7xl')
                ->closeModalByClickingAway(false)
                ->form([
                    FileUpload::make('csv_file')
                        ->label(__('devices.csv_import.file_label'))
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required()
                        ->disk('local')
                        ->visibility('private')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (! empty($state)) {
                                // Auto-suggest column mappings based on column names
                                $this->autoSuggestMappings($state, $get('delimiter'), $get('has_header'), $set);
                            }
                        }),

                    Select::make('delimiter')
                        ->label(__('devices.csv_import.delimiter_label'))
                        ->options([
                            ',' => __('devices.csv_import.delimiters.comma'),
                            ';' => __('devices.csv_import.delimiters.semicolon'),
                            '\t' => __('devices.csv_import.delimiters.tab'),
                            '|' => __('devices.csv_import.delimiters.pipe'),
                        ])
                        ->default(',')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            // Clear all mapping fields when delimiter changes
                            $set('mapping_name', null);
                            $set('mapping_hostname', null);
                            $set('mapping_mac_address', null);
                            $set('mapping_type', null);
                            $set('mapping_location', null);
                            $set('mapping_status', null);
                            $set('mapping_url', null);
                            $set('mapping_description', null);
                            $set('mapping_primary_ip', null);
                            $set('mapping_secondary_ips', null);

                            // Re-suggest mappings with new delimiter
                            if (! empty($get('csv_file'))) {
                                $this->autoSuggestMappings($get('csv_file'), $state, $get('has_header'), $set);
                            }
                        }),

                    Toggle::make('has_header')
                        ->label(__('devices.csv_import.has_header_label'))
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            // Clear all mapping fields when header setting changes
                            $set('mapping_name', null);
                            $set('mapping_hostname', null);
                            $set('mapping_mac_address', null);
                            $set('mapping_type', null);
                            $set('mapping_location', null);
                            $set('mapping_status', null);
                            $set('mapping_url', null);
                            $set('mapping_description', null);
                            $set('mapping_primary_ip', null);
                            $set('mapping_secondary_ips', null);

                            // Re-suggest mappings with new header setting
                            if (! empty($get('csv_file'))) {
                                $this->autoSuggestMappings($get('csv_file'), $get('delimiter'), $state, $set);
                            }
                        }),

                    Placeholder::make('preview')
                        ->label(__('devices.csv_import.preview_label'))
                        ->content(function ($get) {
                            return $this->getCsvPreview($get('csv_file'), $get('delimiter'), $get('has_header'));
                        })
                        ->reactive()
                        ->visible(fn ($get) => ! empty($get('csv_file'))),

                    // Column mapping fields
                    Section::make(__('devices.csv_import.column_mapping_title'))
                        ->description(__('devices.csv_import.column_mapping_description'))
                        ->schema([
                            Select::make('mapping_name')
                                ->label(__('devices.csv_import.fields.name'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->required()
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_hostname')
                                ->label(__('devices.csv_import.fields.hostname'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_mac_address')
                                ->label(__('devices.csv_import.fields.mac_address'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_type')
                                ->label(__('devices.csv_import.fields.type'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_location')
                                ->label(__('devices.csv_import.fields.location'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_status')
                                ->label(__('devices.csv_import.fields.status'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_url')
                                ->label(__('devices.csv_import.fields.url'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_description')
                                ->label(__('devices.csv_import.fields.description'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_primary_ip')
                                ->label(__('devices.csv_import.fields.primary_ip'))
                                ->helperText(__('devices.csv_import.helper_texts.primary_ip'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),

                            Select::make('mapping_secondary_ips')
                                ->label(__('devices.csv_import.fields.secondary_ips'))
                                ->helperText(__('devices.csv_import.helper_texts.secondary_ips'))
                                ->options(function ($get) {
                                    return $this->getColumnOptions($get('csv_file'), $get('delimiter'), $get('has_header'));
                                })
                                ->searchable()
                                ->reactive(),
                        ])
                        ->columns(2)
                        ->visible(fn ($get) => ! empty($get('csv_file'))),

                    Select::make('duplicate_handling')
                        ->label(__('devices.csv_import.duplicate_handling_label'))
                        ->helperText(__('devices.csv_import.duplicate_handling_description'))
                        ->options([
                            'skip' => __('devices.csv_import.duplicate_options.skip'),
                            'overwrite' => __('devices.csv_import.duplicate_options.overwrite'),
                            'merge' => __('devices.csv_import.duplicate_options.merge'),
                        ])
                        ->default('skip')
                        ->required()
                        ->visible(fn ($get) => ! empty($get('csv_file'))),
                ])
                ->action(function (array $data) {
                    $this->executeImport($data);
                }),
        ];
    }

    /**
     * Create HTML preview of the CSV file.
     *
     * Reads the first 5 lines of the CSV file and creates a formatted
     * HTML table for display in the modal. Handles various delimiters
     * and header options.
     *
     * @param  mixed  $filePath  Path to CSV file (can be TemporaryUploadedFile or string)
     * @param  string  $delimiter  CSV delimiter (,;\t|)
     * @param  bool  $hasHeader  Whether the CSV file has a header row
     * @return \Illuminate\Support\HtmlString HTML preview of CSV data
     */
    protected function getCsvPreview($filePath, $delimiter, $hasHeader)
    {
        if (empty($filePath)) {
            return new \Illuminate\Support\HtmlString('<p class="text-gray-500">'.__('devices.csv_import.preview_messages.upload_file').'</p>');
        }

        try {
            // Handle TemporaryUploadedFile from FileUpload component
            if (is_array($filePath)) {
                if (empty($filePath)) {
                    return new \Illuminate\Support\HtmlString('<p class="text-red-500">'.__('devices.csv_import.preview_messages.invalid_file').'</p>');
                }
                // Get first value from associative array
                $uploadedFile = reset($filePath);
            } else {
                $uploadedFile = $filePath;
            }

            // Get real path from TemporaryUploadedFile
            if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
                $fullPath = $uploadedFile->getRealPath();
            } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'path')) {
                $fullPath = $uploadedFile->path();
            } else {
                // Fallback for string paths
                $fullPath = Storage::path($uploadedFile);
            }

            if (! file_exists($fullPath)) {
                return new \Illuminate\Support\HtmlString('<p class="text-red-500">'.__('devices.csv_import.preview_messages.file_not_found', ['path' => $fullPath]).'</p>');
            }

            $csv = Reader::from($fullPath, 'r');
            $delim = $delimiter === '\t' ? "\t" : $delimiter;
            $csv->setDelimiter($delim);
            $stmt = new Statement()->limit(5);
            $records = $stmt->process($csv);
            $preview = iterator_to_array($records);

            if (empty($preview)) {
                return new \Illuminate\Support\HtmlString('<p class="text-red-500">'.__('devices.csv_import.preview_messages.empty_file').'</p>');
            }

            $html = '<div class="overflow-x-auto border-2 border-red-800 rounded bg-red-50"><table class="min-w-full divide-y divide-red-200">';

            foreach ($preview as $rowIndex => $row) {
                $rowArray = array_values((array) $row);
                $isHeader = $hasHeader && $rowIndex === 0;
                $bgClass = $isHeader ? 'bg-red-500 font-semibold' : ($rowIndex % 2 === 0 ? 'bg-red-400 text-black' : 'bg-white text-black');

                $html .= "<tr class='{$bgClass}'>";
                foreach ($rowArray as $cell) {
                    $cellContent = htmlspecialchars(substr($cell, 0, 50));
                    if (strlen($cell) > 50) {
                        $cellContent .= '...';
                    }
                    $html .= "<td class='px-3 py-2 text-sm border-r border-red-500'>{$cellContent}</td>";
                }
                $html .= '</tr>';
            }

            $html .= '</table></div>';

            return new \Illuminate\Support\HtmlString($html);
        } catch (\Exception $e) {
            return new \Illuminate\Support\HtmlString('<p class="text-red-500">'.__('devices.csv_import.preview_messages.read_error', ['error' => $e->getMessage()]).'</p>');
        }
    }

    /**
     * Generate column options for CSV mapping.
     *
     * Reads the first line of the CSV file and creates options for
     * column mapping. Uses column names if header exists,
     * otherwise column numbers with example values.
     *
     * @param  mixed  $filePath  Path to CSV file (can be TemporaryUploadedFile or string)
     * @param  string  $delimiter  CSV delimiter (,;\t|)
     * @param  bool  $hasHeader  Whether the CSV file has a header row
     * @return array<string, string> Array with column options for Select fields
     */
    protected function getColumnOptions($filePath, $delimiter, $hasHeader)
    {
        if (empty($filePath)) {
            return ['ignore' => __('devices.csv_import.column_options.ignore')];
        }

        try {
            // Handle TemporaryUploadedFile from FileUpload component
            if (is_array($filePath)) {
                if (empty($filePath)) {
                    return ['ignore' => __('devices.csv_import.column_options.ignore')];
                }
                // Get first value from associative array
                $uploadedFile = reset($filePath);
            } else {
                $uploadedFile = $filePath;
            }

            // Get real path from TemporaryUploadedFile
            if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
                $fullPath = $uploadedFile->getRealPath();
            } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'path')) {
                $fullPath = $uploadedFile->path();
            } else {
                // Fallback for string paths
                $fullPath = Storage::path($uploadedFile);
            }

            if (! file_exists($fullPath)) {
                return ['ignore' => __('devices.csv_import.column_options.ignore')];
            }

            $csv = Reader::from($fullPath, 'r');
            $delim = $delimiter === '\t' ? "\t" : $delimiter;
            $csv->setDelimiter($delim);

            $stmt = new Statement()->limit(1);
            $records = $stmt->process($csv);
            $firstRow = (array) (iterator_to_array($records)[0] ?? []);

            $options = ['ignore' => __('devices.csv_import.column_options.ignore')];

            if ($hasHeader) {
                // When header exists, use column names
                foreach ($firstRow as $index => $columnName) {
                    $options["col_{$index}"] = __('devices.csv_import.column_options.column_with_name', ['name' => trim($columnName)]);
                }
            } else {
                // When no header, use column numbers with example values
                foreach ($firstRow as $index => $value) {
                    $exampleValue = htmlspecialchars(substr(trim($value), 0, 20));
                    if (strlen(trim($value)) > 20) {
                        $exampleValue .= '...';
                    }
                    $options["col_{$index}"] = __('devices.csv_import.column_options.column_with_example', ['number' => ($index + 1), 'example' => $exampleValue]);
                }
            }

            return $options;
        } catch (\Exception $e) {
            return ['ignore' => __('devices.csv_import.column_options.ignore')];
        }
    }

    /**
     * Execute CSV import of devices and IP addresses.
     *
     * Processes the uploaded CSV file based on mapping settings
     * and imports devices with their IP addresses. Supports various
     * duplicate handling strategies and IPv4/IPv6 addresses.
     *
     * @param  array<string, mixed>  $data  Form data with CSV file and mapping settings
     *
     * @throws \Exception On errors during import
     */
    protected function executeImport(array $data): void
    {
        try {
            // Handle TemporaryUploadedFile from FileUpload component
            if (is_array($data['csv_file'])) {
                if (empty($data['csv_file'])) {
                    throw new \Exception(__('devices.csv_import.error_messages.no_valid_file'));
                }
                // Get first value from associative array
                $uploadedFile = reset($data['csv_file']);
            } else {
                $uploadedFile = $data['csv_file'];
            }

            // Get real path from TemporaryUploadedFile
            if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
                $filePath = $uploadedFile->getRealPath();
                $csvFile = null; // No need to delete temp files
            } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'path')) {
                $filePath = $uploadedFile->path();
                $csvFile = null; // No need to delete temp files
            } else {
                // Fallback for string paths (stored files)
                $csvFile = $uploadedFile;
                $filePath = Storage::path($uploadedFile);
            }

            $csv = Reader::from($filePath, 'r');
            $delimiter = $data['delimiter'] === '\t' ? "\t" : $data['delimiter'];
            $csv->setDelimiter($delimiter);

            // Wenn Header vorhanden, erste Zeile überspringen
            if ($data['has_header']) {
                $csv->setHeaderOffset(0);
                $records = $csv->getRecords();
            } else {
                $records = $csv->getRecords();
            }

            // Mapping erstellen
            $fieldMapping = [];
            foreach ($data as $key => $value) {
                if (strpos($key, 'mapping_') === 0 && $value !== 'ignore' && ! empty($value)) {
                    $field = str_replace('mapping_', '', $key);
                    $columnIndex = (int) str_replace('col_', '', $value);
                    $fieldMapping[$field] = $columnIndex;
                }
            }

            $duplicateHandling = $data['duplicate_handling'];
            $imported = 0;
            $skipped = 0;
            $errors = 0;
            $ipAssignments = 0;

            foreach ($records as $rowIndex => $record) {
                try {
                    $recordArray = array_values((array) $record);
                    $deviceData = [];
                    $primaryIp = null;
                    $secondaryIps = [];

                    // Daten aus CSV-Zeile extrahieren
                    foreach ($fieldMapping as $field => $columnIndex) {
                        if (isset($recordArray[$columnIndex])) {
                            $value = trim($recordArray[$columnIndex]);
                            if (! empty($value)) {
                                if ($field === 'primary_ip') {
                                    $primaryIp = $value;
                                } elseif ($field === 'secondary_ips') {
                                    // Mehrere IPs durch Semikolon getrennt
                                    $secondaryIps = array_filter(array_map('trim', explode(';', $value)));
                                } else {
                                    $deviceData[$field] = $value;
                                }
                            }
                        }
                    }

                    // Name ist Pflichtfeld
                    if (empty($deviceData['name'])) {
                        $errors++;

                        continue;
                    }

                    // Prüfen ob Gerät bereits existiert
                    $existingDevice = Device::where('name', $deviceData['name'])->first();
                    $device = null;

                    if ($existingDevice) {
                        switch ($duplicateHandling) {
                            case 'skip':
                                $skipped++;

                                continue 2;

                            case 'overwrite':
                                // Gerätedaten überschreiben
                                $existingDevice->update($deviceData);
                                $device = $existingDevice;
                                $imported++;
                                break;

                            case 'merge':
                                // Nur leere Felder füllen
                                foreach ($deviceData as $key => $value) {
                                    if (empty($existingDevice->$key)) {
                                        $existingDevice->$key = $value;
                                    }
                                }
                                $existingDevice->save();
                                $device = $existingDevice;
                                $imported++;
                                break;
                        }
                    } else {
                        // Standard-Werte setzen
                        if (! isset($deviceData['status'])) {
                            $deviceData['status'] = 'active';
                        }
                        if (! isset($deviceData['type'])) {
                            $deviceData['type'] = 'other';
                        }

                        $device = Device::create($deviceData);
                        $imported++;
                    }

                    // IP-Adressen verarbeiten, falls Gerät erstellt/aktualisiert wurde
                    if ($device) {
                        // Primäre IP-Adresse hinzufügen
                        if ($primaryIp && $this->isValidIpAddress($primaryIp)) {
                            $ipRecord = $this->createOrGetIpAddress($primaryIp);
                            if ($ipRecord) {
                                // Entferne alle bestehenden primären IP-Zuordnungen wenn overwrite
                                if ($duplicateHandling === 'overwrite') {
                                    $device->ipAddresses()->updateExistingPivot(
                                        $device->ipAddresses->pluck('id')->toArray(),
                                        ['is_primary' => false]
                                    );
                                }

                                // Zuordnung erstellen/aktualisieren
                                $device->ipAddresses()->syncWithoutDetaching([
                                    $ipRecord->id => ['is_primary' => true],
                                ]);
                                $ipAssignments++;
                            }
                        }

                        // Sekundäre IP-Adressen hinzufügen
                        foreach ($secondaryIps as $secondaryIp) {
                            if ($this->isValidIpAddress($secondaryIp)) {
                                $ipRecord = $this->createOrGetIpAddress($secondaryIp);
                                if ($ipRecord) {
                                    $device->ipAddresses()->syncWithoutDetaching([
                                        $ipRecord->id => ['is_primary' => false],
                                    ]);
                                    $ipAssignments++;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;

                    continue;
                }
            }

            // Aufräumen - nur gespeicherte Dateien löschen, nicht temporäre
            if ($csvFile) {
                Storage::delete($csvFile);
            }

            // Success message
            $message = __('devices.csv_import.success_messages.import_completed', ['imported' => $imported]);
            if ($ipAssignments > 0) {
                $message .= ', '.__('devices.csv_import.success_messages.ip_assignments_created', ['count' => $ipAssignments]);
            }
            if ($skipped > 0) {
                $message .= ', '.__('devices.csv_import.success_messages.items_skipped', ['count' => $skipped]);
            }
            if ($errors > 0) {
                $message .= ', '.__('devices.csv_import.success_messages.errors_occurred', ['count' => $errors]);
            }

            Notification::make()
                ->title(__('devices.csv_import.notifications.success_title'))
                ->body($message)
                ->success()
                ->send();

            // Update table
            $this->redirect(request()->header('Referer'));
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('devices.csv_import.notifications.error_title'))
                ->body(__('devices.csv_import.error_messages.import_failed', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }

    /**
     * Validate if a string is a valid IPv4 or IPv6 address.
     *
     * Uses PHP's filter_var with FILTER_VALIDATE_IP for validation
     * and supports both IPv4 and IPv6 addresses.
     *
     * @param  string  $ip  The IP address to validate
     * @return bool True if valid IP address, false otherwise
     */
    private function isValidIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Create a new IP address record or return an existing one.
     *
     * First checks if the IP address already exists in the database.
     * If not, creates a new record with automatic version detection
     * (IPv4/IPv6) and marks it as 'assigned'.
     *
     * @param  string  $ipAddress  The IP address as string
     * @return IpAddress|null IP address model or null on errors
     */
    private function createOrGetIpAddress(string $ipAddress): ?IpAddress
    {
        try {
            // Check if IP already exists
            $existingIp = IpAddress::where('ip_address', $ipAddress)->first();

            if ($existingIp) {
                return $existingIp;
            }

            // Determine IP version
            $version = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6;

            // Create new IP address record
            return IpAddress::create([
                'ip_address' => $ipAddress,
                'version' => $version,
                'status' => 'assigned',
                'group' => 'imported',
                'description' => 'Imported via CSV',
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the whole import
            return null;
        }
    }

    /**
     * Automatically suggest column mappings based on CSV column names.
     *
     * Analyzes CSV column names and tries to match them to database fields
     * using common patterns and keywords. Sets the mapping fields automatically
     * when good matches are found.
     *
     * @param  mixed  $filePath  Path to CSV file
     * @param  string  $delimiter  CSV delimiter
     * @param  bool  $hasHeader  Whether CSV has header row
     * @param  callable  $set  Form state setter function
     */
    protected function autoSuggestMappings($filePath, $delimiter, $hasHeader, $set): void
    {
        if (! $hasHeader || empty($filePath)) {
            return;
        }

        try {
            // Handle TemporaryUploadedFile from FileUpload component
            if (is_array($filePath)) {
                if (empty($filePath)) {
                    return;
                }
                $uploadedFile = reset($filePath);
            } else {
                $uploadedFile = $filePath;
            }

            // Get real path from TemporaryUploadedFile
            if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
                $fullPath = $uploadedFile->getRealPath();
            } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'path')) {
                $fullPath = $uploadedFile->path();
            } else {
                $fullPath = Storage::path($uploadedFile);
            }

            if (! file_exists($fullPath)) {
                return;
            }

            $csv = Reader::from($fullPath, 'r');
            $delim = $delimiter === '\t' ? "\t" : $delimiter;
            $csv->setDelimiter($delim);

            $stmt = new Statement()->limit(1);
            $records = $stmt->process($csv);
            $firstRow = (array) (iterator_to_array($records)[0] ?? []);

            // Define mapping patterns for automatic suggestion
            $patterns = [
                'name' => ['name', 'device_name', 'devicename', 'device', 'titel', 'title'],
                'hostname' => ['hostname', 'host_name', 'host', 'fqdn', 'server_name', 'servername'],
                'mac_address' => ['mac', 'mac_address', 'macaddress', 'mac_addr', 'physical_address'],
                'type' => ['type', 'device_type', 'devicetype', 'typ', 'kategorie', 'category'],
                'location' => ['location', 'standort', 'site', 'ort', 'position', 'place'],
                'status' => ['status', 'state', 'zustand', 'condition'],
                'url' => ['url', 'link', 'website', 'web', 'address'],
                'description' => ['description', 'beschreibung', 'desc', 'comment', 'kommentar', 'notes', 'notizen'],
                'primary_ip' => ['ip', 'ip_address', 'primary_ip', 'main_ip', 'ipaddress', 'ip4', 'ipv4', 'ip_addr'],
                'secondary_ips' => ['secondary_ip', 'secondary_ips', 'additional_ip', 'extra_ip', 'other_ip', 'weitere_ip'],
            ];

            // Try to match column names to database fields
            foreach ($firstRow as $index => $columnName) {
                $columnName = strtolower(trim($columnName));

                foreach ($patterns as $field => $keywords) {
                    foreach ($keywords as $keyword) {
                        // remove _ and spaces for better matching
                        $normalizedColumn = str_replace(['_', ' '], '', $columnName);
                        $normalizedKeyword = strtolower(str_replace(['_', ' '], '', $keyword));
                        if ($normalizedColumn === $normalizedKeyword) {
                            $set("mapping_{$field}", "col_{$index}");
                            break 2; // Break both loops once we found a match
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if auto-suggestion doesn't work
        }
    }
}
