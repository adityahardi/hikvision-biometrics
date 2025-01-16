<?php

namespace App\Services\Checkpoint;

use App\Models\Employee;
use App\Models\Checkpoint;
use Illuminate\Support\Carbon;
use App\Helpers\CheckpointHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\DTOs\CheckpointServiceResponderDto;

class CheckpointService
{
    public function syncUserCheckpoint(Checkpoint $checkpoint, Employee $employee): CheckpointServiceResponderDto
    {
        try {
            $userCheckpointDelete = CheckpointHelper::userCheckpointDelete($checkpoint, $employee->employee_id);

            if (!$userCheckpointDelete->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_USER_DELETE);
            }

            $userCheckpointStore = CheckpointHelper::userCheckpointStore($checkpoint, $employee->employee_id, $employee->name);

            if (!$userCheckpointStore->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_USER_CREATE);
            }

            if ($employee->faceBiometric) {
                $userFaceCheckpointStore = CheckpointHelper::userFaceCheckpointStore($checkpoint, $employee->employee_id, $employee->faceBiometric->data);

                if (!$userFaceCheckpointStore->success) {
                    return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_FACE);
                }
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function syncUserFaceBiometric(Checkpoint $checkpoint, Employee $employee): CheckpointServiceResponderDto
    {
        try {
            $userFaceCheckpointDelete = CheckpointHelper::userFaceCheckpointDelete($checkpoint, $employee->employee_id);

            if (!$userFaceCheckpointDelete->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_FACE_DELETE);
            }

            if ($employee->faceBiometric) {
                $userFaceCheckpointStore = CheckpointHelper::userFaceCheckpointStore($checkpoint, $employee->employee_id, $employee->faceBiometric->data);

                if (!$userFaceCheckpointStore->success) {
                    return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_FACE);
                }
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function modifyUserCheckpoint(Checkpoint $checkpoint, Employee $employee): CheckpointServiceResponderDto
    {
        try {
            $validEndTime = null;
            $validStartTime = null;
            if ($employee->resign_date) {
                $resignDate = Carbon::parse($employee->resign_date);
                $validStartTime = !$resignDate->greaterThan(now()->format('Y-m-d')) ? $resignDate->subWeek()->format('Y-m-d') . 'T' . $resignDate->format('H:i:s') : null;
                $validEndTime = $resignDate->format('Y-m-d') . 'T' . $resignDate->format('H:i:s');
            }

            $userCheckpointModify = CheckpointHelper::userCheckpointModify($checkpoint, $employee->employee_id, $employee->user->full_name, $employee->resign_date, $validStartTime, $validEndTime);

            if (!$userCheckpointModify->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_USER_MODIFY);
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function checkConnectionStatusCheckpoint(Checkpoint $checkpoint): CheckpointServiceResponderDto
    {
        try {
            $userCount = CheckpointHelper::userCount($checkpoint);

            if (!$userCount->success) {
                return new CheckpointServiceResponderDto(errorType: 'offline');
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function deleteUserCheckpoint(Checkpoint $checkpoint, Employee $employee): CheckpointServiceResponderDto
    {
        try {
            $userCheckpointDelete = CheckpointHelper::userCheckpointDelete($checkpoint, $employee->employee_id);

            if (!$userCheckpointDelete->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_USER_DELETE);
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function deleteAllUserCheckpoint(Checkpoint $checkpoint): CheckpointServiceResponderDto
    {
        try {
            $userCheckpointDelete = CheckpointHelper::userCheckpointDeleteAll($checkpoint);

            if (!$userCheckpointDelete->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_USER_DELETE);
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function updateDeviceName(Checkpoint $checkpoint, string $deviceName): CheckpointServiceResponderDto
    {
        try {
            $capability = CheckpointHelper::capabilityDeviceInfo($checkpoint);

            if (!$capability->success || !isset($capability->data->deviceName)) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::CAPABILITY);
            }

            $maxName = intval($capability->data->deviceName?->{'@attributes'}?->max);

            $deviceName = str($deviceName)->limit($maxName, '');

            $updateDeviceName = CheckpointHelper::updateDeviceName($checkpoint, $deviceName);

            if (!$updateDeviceName->success) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function updateDeviceTimeZone(Checkpoint $checkpoint): CheckpointServiceResponderDto
    {
        try {
            $capability = CheckpointHelper::capabilityDeviceTimeZone($checkpoint);

            if (!$capability->success || !isset($capability->data->NTPServer)) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::CAPABILITY);
            }

            $update = CheckpointHelper::updateDeviceTimeZone($checkpoint, now()->format('Y-m-d\TH:i:s'));

            if (!$update->success || !isset($update->data->statusCode) || intval($update->data->statusCode) !== 1) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function rebootCheckpoint(Checkpoint $checkpoint): CheckpointServiceResponderDto
    {
        try {
            $reboot = CheckpointHelper::rebootCheckpoint($checkpoint);

            if (!$reboot->success) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::ERROR_REBOOT_DEVICE);
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function updateDeviceAccessConfig(
        Checkpoint $checkpoint,
        bool $showPicture = false,
        bool $showName = true,
        bool $showEmployeeNo = false,
        bool $desensitiseName = false,
        bool $uploadVerificationPic = true,
        bool $saveVerificationPic = true,
        bool $saveFacePic = false,
        bool $uploadCapPic = false,
        bool $saveCapPic = false,
    ): CheckpointServiceResponderDto {
        try {
            $capability = CheckpointHelper::capabilityDeviceAccessConfig($checkpoint);

            if (!$capability->success || !isset(json_decode($capability->data)->AcsCfg)) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::CAPABILITY);
            }

            $update = CheckpointHelper::updateDeviceAccessConfig(
                $checkpoint,
                $uploadCapPic,
                $saveCapPic,
                true,
                $showPicture,
                $showEmployeeNo,
                $showName,
                false,
                $desensitiseName,
                $uploadVerificationPic,
                $saveVerificationPic,
                $saveFacePic,
            );

            if (!$update->success) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function createDeviceHttpHostNotification(Checkpoint $checkpoint, ?string $url = null): CheckpointServiceResponderDto
    {
        try {
            $capability = CheckpointHelper::capabilityDeviceHttpHostNotification($checkpoint);

            if (!$capability->success || !isset($capability->data->hostName) || !isset($capability->data->ipAddress)) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::CAPABILITY);
            }

            $url ??= route('api.isup.event');

            $getHostNotifications = CheckpointHelper::getDeviceHttpHostNotifications($checkpoint);

            if (!$getHostNotifications->success) {
                return new CheckpointServiceResponderDto();
            }

            $httpHostNotifications = collect(json_decode(json_encode($getHostNotifications->data), true)['HttpHostNotification']);

            $lastHostId = $httpHostNotifications->last()['id'] ?? 1;

            $create = CheckpointHelper::createDeviceHttpHostNotification($checkpoint, $url, $lastHostId + 1);

            if (!$create->success || !isset($create->data->statusCode) || intval($create->data->statusCode) !== 1) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            dd($th);
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function updateDeviceHttpHostNotification(Checkpoint $checkpoint, ?string $url = null): CheckpointServiceResponderDto
    {
        try {
            $capability = CheckpointHelper::capabilityDeviceHttpHostNotification($checkpoint);

            if (!$capability->success || !isset($capability->data->hostName) || !isset($capability->data->ipAddress)) {
                return new CheckpointServiceResponderDto(errorType: CheckpointHelper::CAPABILITY);
            }

            $getHostNotifications = CheckpointHelper::getDeviceHttpHostNotifications($checkpoint);

            if (!$getHostNotifications->success) {
                return new CheckpointServiceResponderDto();
            }

            $httpHostNotifications = collect(json_decode(json_encode($getHostNotifications->data), true)['HttpHostNotification']);

            $subscribeEvents = $httpHostNotifications->filter(function ($item) {
                return isset($item['SubscribeEvent']) && isset($item['url']) && !empty($item['url']);
            });

            $url ??= route('api.isup.event');
            $urlParts = parse_url($url);

            $protocol = strtoupper($urlParts['scheme']);
            $host = $urlParts['host'];
            $path = $urlParts['path'] ?? null;
            $isIp = filter_var($host, FILTER_VALIDATE_IP);
            $port = $urlParts['port'] ?? ($protocol === 'HTTPS' ? 443 : 80);

            $subscribeEventThisApp = $subscribeEvents->where(function ($item) use ($isIp, $host, $port, $path) {
                return (
                    $item['addressingFormatType'] == ($isIp ? 'ipaddress' : 'hostname') &&
                    ($host == ($isIp ? $item['ipAddress'] : $item['hostName'])) &&
                    $item['portNo'] == $port &&
                    $item['url'] == $path
                );
            });

            $lastHostId = $subscribeEventThisApp->count() > 0 ? ($subscribeEventThisApp->first()['id'] ?? 1) : ($subscribeEvents->first()['id'] ?? 1);

            $update = CheckpointHelper::updateDeviceHttpHostNotification($checkpoint, $url, $lastHostId);

            if (!$update->success || !isset($update->data->statusCode) || intval($update->data->statusCode) !== 1) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }

    public function updateDeviceNTPServer(Checkpoint $checkpoint, string $hostName = 'ntp.cigs.net.id', int $port = 123, int $synchronizeInterval = 180): CheckpointServiceResponderDto
    {
        try {
            $update = CheckpointHelper::updateDeviceNTPServer($checkpoint, $hostName, $port, $synchronizeInterval);

            if (!$update->success || !isset($update->data->statusCode) || intval($update->data->statusCode) !== 1) {
                return new CheckpointServiceResponderDto();
            }

            return new CheckpointServiceResponderDto(true);
        } catch (\Throwable $th) {
            Log::warning($th);

            return new CheckpointServiceResponderDto(false);
        }
    }
}
