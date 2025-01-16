<?php

namespace App\Helpers;

use App\Models\Checkpoint;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\DTOs\CheckpointResponseDto;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\DTOs\CheckpointDeviceInfoDto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\PendingRequest;

class CheckpointHelper
{
    public const STORAGE_PATH = 'biometrics';

    public const CAPTURE_FINGER_PRINT_COND = '<CaptureFingerPrintCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema"><fingerNo>1</fingerNo></CaptureFingerPrintCond>';

    public const CAPTURE_FACE_DATA_COND = '<CaptureFaceDataCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema"><dataType>binary</dataType></CaptureFaceDataCond>';

    public const ERROR_USER_DELETE = 'user_delete';

    public const ERROR_USER_CREATE = 'user_create';

    public const ERROR_FACE = 'face';

    public const ERROR_FINGERPRINT = 'fingerprint';

    public const ERROR_CARD = 'card';

    public const ERROR_FACE_DELETE = 'face_delete';

    public const ERROR_FINGERPRINT_DELETE = 'fingerprint_delete';

    public const ERROR_CARD_DELETE = 'card_delete';

    public const ERROR_USER_MODIFY = 'user_modify';

    public const ERROR_REBOOT_DEVICE = 'reboot_device';

    public const CAPABILITY = 'capability';

    public static function createRequest(Checkpoint $checkpoint): PendingRequest
    {
        return Http::baseUrl("http://{$checkpoint->ip}")
            ->withDigestAuth($checkpoint->username, $checkpoint->password);
    }

    /**
     * Sends a request to a specified path using the given method and data.
     *
     * @param Checkpoint $checkpoint The checkpoint object containing the IP, username, and password.
     * @param string $method The HTTP method to use for the request.
     * @param string $path The path to send the request to.
     * @param mixed $data The data to send with the request (optional).
     * @return Response The response from the request.
     */
    public static function sendRequest(Checkpoint $checkpoint, string $method, string $path, mixed $data = null): Response
    {
        return match($method) {
            'POST' => self::createRequest($checkpoint)->post($path, $data),
            'PUT' => self::createRequest($checkpoint)->put($path, $data),
            'DELETE' => self::createRequest($checkpoint)->delete($path, $data),
            'PATCH' => self::createRequest($checkpoint)->patch($path, $data),
            default => self::createRequest($checkpoint)->get($path, $data),
        };
    }

    /**
     * Sends a POST request to the given path using HTTP.
     *
     * @param Checkpoint $checkpoint The checkpoint object.
     * @param string $path The path to send the request to.
     * @param mixed $body The body of the request (optional, default: null).
     * @param string $contentType The content type of the request (optional, default: 'application/xml').
     * @return Response The response from the POST request.
     */
    public static function postRequest(Checkpoint $checkpoint, string $path, mixed $body = null, string $contentType = 'application/xml'): Response
    {
        if (is_string($body)) {
            return self::createRequest($checkpoint)->withBody($body, $contentType)->post($path);
        }

        return self::createRequest($checkpoint)->post($path, $body);
    }

    /**
     * Capture a fingerprint from a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint object.
     * @return CheckpointResponseDto The captured fingerprint.
     */
    public static function captureFingerprint(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $fingerprintRequest = self::postRequest($checkpoint, '/ISAPI/AccessControl/CaptureFingerPrint', self::CAPTURE_FINGER_PRINT_COND);

        $data = $fingerprintRequest->body();
        $data = json_encode($data);
        $data = json_decode($data);

        return ($fingerprintRequest->status() != 200)
            ? new CheckpointResponseDto($fingerprintRequest)
            : new CheckpointResponseDto($fingerprintRequest, true, str($data)->betweenFirst('<fingerData>', '</fingerData>')->value);
    }

    /**
     * Capture a face using the given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint object.
     * @return CheckpointResponseDto The response object containing the captured face data.
     */
    public static function captureFace(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $faceRequest = self::postRequest($checkpoint, '/ISAPI/AccessControl/CaptureFaceData', self::CAPTURE_FACE_DATA_COND);

        if ($faceRequest->status() != 200 || (str($faceRequest->body())->betweenFirst('<captureProgress>', '</captureProgress>')->value == 0)) {
            return new CheckpointResponseDto($faceRequest);
        }

        $binaryData = $faceRequest->body();
        $payload = substr($binaryData, 360);

        $ulid = str(Str::ulid())->lower();

        $fileName = self::STORAGE_PATH . "/{$ulid}.jpeg";

        Storage::put($fileName, $payload);

        return new CheckpointResponseDto($faceRequest, true, $fileName);
    }

    /**
     * Stores user checkpoint information.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @param string $employeeNo The employee number.
     * @param string $name The name of the user.
     * @param string|null $validStart The start date and time of validity.
     * @param string|null $validEnd The end date and time of validity.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function userCheckpointStore(Checkpoint $checkpoint, string $employeeNo, string $name, ?string $validStart = null, ?string $validEnd = null): CheckpointResponseDto
    {
        $validStart = $validStart ? Carbon::parse($validStart) : Carbon::parse('01-01-2023 01:00:00');
        $validEnd = $validEnd ? Carbon::parse($validEnd) : Carbon::now()->addYears(7);

        $data = [
            'UserInfo' => [
                'employeeNo' => $employeeNo,
                'deleteUser' => false,
                'name' => $name,
                'userType' => 'normal',
                'closeDelayEnabled' => true,
                'Valid' => [
                    'enable' => true,
                    'beginTime' => $validStart->format('Y-m-d') . 'T' . $validStart->format('H:i:s'),
                    'endTime' => $validEnd->format('Y-m-d') . 'T' . $validEnd->format('H:i:s'),
                    'timeType' => 'local'
                ],
                'doorRight' => '1',
                'RightPlan' => [
                    [
                        'doorNo' => 1,
                        'planTemplateNo' => '1',
                    ],
                ],
                'userVerifyMode' => '',
            ],
        ];

        $userStore = self::postRequest($checkpoint, '/ISAPI/AccessControl/UserInfo/Record?format=json', $data, 'application/json');

        return new CheckpointResponseDto($userStore, $userStore->ok(), $userStore->body());
    }

    /**
     * Modify user checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to modify.
     * @param string $employeeNo The employee number.
     * @param string $name The name of the user.
     * @param string|null $validStart The start of the validity period (optional).
     * @param string|null $validEnd The end of the validity period (optional).
     * @return CheckpointResponseDto The modified user store.
     */
    public static function userCheckpointModify(Checkpoint $checkpoint, string $employeeNo, string $name, ?string $validStart = null, ?string $validEnd = null): CheckpointResponseDto
    {
        $validStart = $validStart ? Carbon::parse($validStart) : Carbon::parse(now());
        $validEnd = $validEnd ? Carbon::parse($validEnd) : Carbon::now()->addYears(7);

        $data = [
            'UserInfo' => [
                'employeeNo' => $employeeNo,
                'name' => $name,
                'userType' => 'normal',
                'closeDelayEnabled' => true,
                'Valid' => [
                    'enable' => true,
                    'beginTime' => $validStart->format('Y-m-d') . 'T' . $validStart->format('H:i:s'),
                    'endTime' => $validEnd->format('Y-m-d') . 'T' . $validEnd->format('H:i:s'),
                    'timeType' => 'local'
                ],
                'doorRight' => '1',
                'RightPlan' => [
                    [
                        'doorNo' => 1,
                        'planTemplateNo' => '1',
                    ],
                ],
                'userVerifyMode' => '',
            ],
        ];

        $userStore = self::createRequest($checkpoint)->put('/ISAPI/AccessControl/UserInfo/Modify?format=json', $data);

        return new CheckpointResponseDto($userStore, $userStore->ok(), $userStore->body());
    }


    /**
     * Stores a user card checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @param string $employeeNo The employee number.
     * @param string $cardNo The card number.
     * @return CheckpointResponseDto The response from the `responder` method.
     */
    public static function userCardCheckpointStore(Checkpoint $checkpoint, string $employeeNo, string $cardNo): CheckpointResponseDto
    {
        $data = [
            'CardInfo' => [
                'employeeNo' => strval($employeeNo),
                'cardNo' => strval($cardNo),
                'cardType' => 'normalCard',
            ],
        ];

        $cardStore = self::postRequest($checkpoint, '/ISAPI/AccessControl/CardInfo/Record?format=json', $data, 'application/json');

        return new CheckpointResponseDto($cardStore, $cardStore->ok(), $cardStore->body());
    }

    /**
     * Deletes the specified cards from the given checkpoint.
     * when cardsNo is empty, it will delete all cards in the checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint from which to delete the cards.
     * @param array $cardsNo (e.g ['1111', '2222', '3333']) An array of card numbers to delete.
     * @return CheckpointResponseDto The response from the API after deleting the cards.
     */
    public static function userCardCheckpointDelete(Checkpoint $checkpoint, array $cardsNo): CheckpointResponseDto
    {
        $data = [
            'CardInfoDelCond' => [
                'CardNoList' => collect($cardsNo)->map(function ($item) {
                    return ['cardNo' => $item];
                })->toArray(),
            ],
        ];

        $cardDelete = self::createRequest($checkpoint)->put('/ISAPI/AccessControl/CardInfo/Delete?format=json', $data);

        return new CheckpointResponseDto($cardDelete, $cardDelete->ok(), $cardDelete->body());
    }

    /**
     * Stores a user's finger checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @param string $employeeNo The employee number.
     * @param int $fingerId The finger ID.
     * @param string $fingerData The finger data.
     * @return CheckpointResponseDto The response object.
     */
    public static function userFingerCheckpointStore(Checkpoint $checkpoint, string $employeeNo, int $fingerId, string $fingerData): CheckpointResponseDto
    {
        $data = [
            'FingerPrintCfg' => [
                'employeeNo' => $employeeNo,
                'enableCardReader' => [1],
                'fingerPrintID' => $fingerId,
                'fingerType' => 'normalFP',
                'fingerData' => $fingerData
            ]
        ];

        $fingerStore = self::postRequest($checkpoint, '/ISAPI/AccessControl/FingerPrint/SetUp?format=json', $data, 'application/json');

        return new CheckpointResponseDto($fingerStore, ($fingerStore->ok() && $fingerStore->collect('FingerPrintStatus')['StatusList'][0]['cardReaderRecvStatus'] > 0), $fingerStore->body());
    }

    public static function userFingerprintCheckpointDelete(Checkpoint $checkpoint, string $employeeNo): CheckpointResponseDto
    {
        $data = [
            'FingerPrintDelete' => [
                'mode' => 'byEmployeeNo',
                'EmployeeNoDetail' => [
                    'employeeNo' => $employeeNo,
                ],
            ],
        ];

        $cardDelete = self::createRequest($checkpoint)->put('ISAPI/AccessControl/FingerPrint/Delete?format=json', $data);

        return new CheckpointResponseDto($cardDelete, $cardDelete->ok(), $cardDelete->body());
    }

    /**
     * Stores the user face checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @param string $employeeNo The employee number.
     * @param string $faceUrl The URL of the face image.
     * @param ?string $bornDate The birth date of the user. Default is '2004-05-03'.
     * @return CheckpointResponseDto
     */
    public static function userFaceCheckpointStore(Checkpoint $checkpoint, string $employeeNo, string $faceUrl, ?string $bornDate = '2003-08-11'): CheckpointResponseDto
    {
        $face = self::storeImageToStorage($faceUrl);

        $data = [
            'faceURL' => $face,
            'faceLibType' => 'blackFD',
            'FDID' => '1',
            'FPID' => strval($employeeNo),
            'name' => 'Employee Face ' . $employeeNo,
            'bornTime' => $bornDate ? $bornDate : '2003-08-11',
        ];

        try {
            $faceStore = self::createRequest($checkpoint)->post('/ISAPI/Intelligent/FDLib/FaceDataRecord?format=json', $data);

            return new CheckpointResponseDto($faceStore, ($faceStore->json()['statusCode'] ?? null) === 1, $faceStore->body());
        } catch (\Throwable $th) {
            Log::error($th);
        }

        $existStore = self::postRequest($checkpoint, '/ISAPI/Intelligent/FDLib/FDSearch?format=json', [
            'searchResultPosition' => 0,
            'maxResults' => 100,
            'faceLibType' => 'blackFD',
            'FDID' => '1',
            'FPID' => $employeeNo,
        ]);

        if ($face) {
            Storage::delete($face);
        }

        return new CheckpointResponseDto($existStore, isset($existStore->collect('MatchList')[0]['FPID']), $existStore->body());
    }

    /**
     * Deletes a user's face checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to delete.
     * @param string $employeeNo The employee number of the user.
     * @return CheckpointResponseDto The response from the delete request.
     */
    public static function userFaceCheckpointDelete(Checkpoint $checkpoint, string $employeeNo): CheckpointResponseDto
    {
        $data = [
            'FPID' => [
                [
                    'value' => $employeeNo,
                ],
            ],
        ];

        $faceDelete = self::createRequest($checkpoint)->put("/ISAPI/Intelligent/FDLib/FDSearch/Delete?format=json&FDID=1&faceLibType=blackFD", $data);

        return new CheckpointResponseDto($faceDelete, $faceDelete->ok(), $faceDelete->ok());
    }

    /**
     * Deletes all user checkpoints.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @return CheckpointResponseDto The response object.
     */
    public static function userCheckpointDeleteAll(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $data = [
            'UserInfoDetail' => [
                'mode' => 'all',
            ],
        ];

        $deleteUser = self::sendRequest($checkpoint, 'PUT', '/ISAPI/AccessControl/UserInfoDetail/Delete?format=json', $data);

        return new CheckpointResponseDto($deleteUser, $deleteUser->ok(), $deleteUser->body());
    }

    /**
     * Deletes a user checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint model.
     * @param string $employeeNo The employee number.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function userCheckpointDelete(Checkpoint $checkpoint, string $employeeNo): CheckpointResponseDto
    {
        $data = [
            'UserInfoDetail' => [
                'mode' => 'byEmployeeNo',
                'EmployeeNoList' => [
                    [
                        'employeeNo' => $employeeNo,
                    ],
                ],
            ],
        ];

        $deleteUser = self::sendRequest($checkpoint, 'PUT', '/ISAPI/AccessControl/UserInfoDetail/Delete?format=json', $data);

        return new CheckpointResponseDto($deleteUser, $deleteUser->ok(), $deleteUser->body());
    }

    /**
     * Retrieves the count of users from the given Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint model to send the request to.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function userCount(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $countUser = self::sendRequest($checkpoint, 'GET', '/ISAPI/AccessControl/UserInfo/Count?format=json');

        return new CheckpointResponseDto($countUser, $countUser->ok(), $countUser->body());
    }

    /**
     * Retrieves the HTTP host notifications from the given Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint model to send the request to.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function getDeviceHttpHostNotifications(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $httpHostNotifications = self::createRequest($checkpoint)->get('/ISAPI/Event/notification/httpHosts');

        return new CheckpointResponseDto($httpHostNotifications, $httpHostNotifications->ok(), simplexml_load_string($httpHostNotifications->body()));
    }

    /**
     * Retrieves the HTTP host notifications from the given Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint model to send the request to.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function getDeviceInfo(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $getDeviceInfo = self::createRequest($checkpoint)->get('/ISAPI/System/deviceInfo');

        return new CheckpointResponseDto($getDeviceInfo, $getDeviceInfo->ok(), $getDeviceInfo->body());
    }

    /**
     * Updates the device info from the given Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint model to send the request to.
     * @param CheckpointDeviceInfoDto $checkpointDeviceInfo The CheckpointDeviceInfoDto model to send the request to.
     * @return CheckpointResponseDto The response from the API call.
     */
    public static function updateDeviceInfo(Checkpoint $checkpoint, CheckpointDeviceInfoDto $checkpointDeviceInfo): CheckpointResponseDto
    {
        $updateDeviceInfo = self::createRequest($checkpoint)->withBody($checkpointDeviceInfo->getXML(), 'text/xml')->put('/ISAPI/System/deviceInfo');

        return new CheckpointResponseDto($updateDeviceInfo, $updateDeviceInfo->ok(), $updateDeviceInfo->body());
    }

    /**
     * Updates the device name in the Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint object.
     * @param string $deviceName The new device name.
     * @return CheckpointResponseDto The response DTO object.
     */
    public static function updateDeviceName(Checkpoint $checkpoint, string $deviceName): CheckpointResponseDto
    {
        $xmlData = <<<XML
            <DeviceInfo version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
                <deviceName>{$deviceName}</deviceName>
            </DeviceInfo>
            XML;

        $updateDeviceInfo = self::createRequest($checkpoint)->withBody($xmlData, 'text/xml')->put('/ISAPI/System/deviceInfo');

        return new CheckpointResponseDto($updateDeviceInfo, $updateDeviceInfo->ok(), $updateDeviceInfo->body());
    }

    /**
     * Retrieves the device info capability of a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to retrieve the system time capability from.
     * @return CheckpointResponseDto The response DTO containing the system time capability.
     */
    public static function capabilityDeviceInfo(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $deviceInfoCapability = self::createRequest($checkpoint)->get('/ISAPI/System/deviceInfo/capabilities');

        $deviceInfoCapabilityData = json_decode(json_encode(simplexml_load_string($deviceInfoCapability->body())));

        return new CheckpointResponseDto($deviceInfoCapability, $deviceInfoCapability->ok(), $deviceInfoCapabilityData);
    }

    /**
     * Retrieves the system time capability of a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to retrieve the system time capability from.
     * @return CheckpointResponseDto The response DTO containing the system time capability.
     */
    public static function capabilityDeviceTimeZone(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $systemTimeCapability = self::createRequest($checkpoint)->get('/ISAPI/System/time/ntpServers/capabilities');

        return new CheckpointResponseDto($systemTimeCapability, $systemTimeCapability->ok(), simplexml_load_string($systemTimeCapability->body()));
    }

    /**
     * Updates the device timezone of a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to update the timezone for.
     * @param string $localTime The local time to set on the device.
     * @param string $timeMode The time mode to use. Default is 'NTP'.
     * @param string $timeZone The timezone to set on the device. Default is 'CST-7:00:00'.
     * @return CheckpointResponseDto The response from the checkpoint with the updated timezone.
     */
    public static function updateDeviceTimeZone(Checkpoint $checkpoint, string $localTime, string $timeMode = 'NTP', string $timeZone = 'CST-7:00:00'): CheckpointResponseDto
    {
        $xmlData = <<<XML
            <Time>
                <timeMode>{$timeMode}</timeMode>
                <localTime>{$localTime}</localTime>
                <timeZone>{$timeZone}</timeZone>
            </Time>
        XML;

        $request = self::createRequest($checkpoint)->withBody($xmlData, 'text/xml')->put('/ISAPI/System/time');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    /**
     * Retrieves the system access config capability of a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to retrieve the system time capability from.
     * @return CheckpointResponseDto The response DTO containing the system time capability.
     */
    public static function capabilityDeviceAccessConfig(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $request = self::createRequest($checkpoint)->get('/ISAPI/AccessControl/AcsCfg/capabilities?format=json');

        return new CheckpointResponseDto($request, $request->ok(), $request->body());
    }

    /**
     * Retrieves the device access configuration for a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint for which to retrieve the access configuration.
     * @return CheckpointResponseDto The response containing the access configuration.
     */
    public static function getDeviceAccessConfig(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $request = self::createRequest($checkpoint)->get('/ISAPI/AccessControl/AcsCfg?format=json');

        return new CheckpointResponseDto($request, $request->ok(), $request->body());
    }

    /**
     * Updates the device access configuration for a checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint for which the access configuration is updated.
     * @param bool $uploadCapPic Whether to upload captured pictures.
     * @param bool $saveCapPic Whether to save captured pictures.
     * @param bool $voicePrompt Whether to enable voice prompts.
     * @param bool $showPicture Whether to show pictures.
     * @param bool $showEmployeeNo Whether to show employee numbers.
     * @param bool $showName Whether to show employee names.
     * @param bool $desensitiseEmployeeNo Whether to desensitize employee numbers.
     * @param bool $desensitiseName Whether to desensitize employee names.
     * @param bool $uploadVerificationPic Whether to upload verification pictures.
     * @param bool $saveVerificationPic Whether to save verification pictures.
     * @param bool $saveFacePic Whether to save face pictures.
     * @return CheckpointResponseDto The response from updating the access configuration.
     */
    public static function updateDeviceAccessConfig(
        Checkpoint $checkpoint,
        bool $uploadCapPic = false,
        bool $saveCapPic = false,
        bool $voicePrompt = true,
        bool $showPicture = false,
        bool $showEmployeeNo = false,
        bool $showName = true,
        bool $desensitiseEmployeeNo = false,
        bool $desensitiseName = false,
        bool $uploadVerificationPic = true,
        bool $saveVerificationPic = true,
        bool $saveFacePic = false,
    ): CheckpointResponseDto {
        $data = [
            'AcsCfg' => [
                'uploadCapPic' => $uploadCapPic,
                'saveCapPic' => $saveCapPic,
                'voicePrompt' => $voicePrompt,
                'showPicture' => $showPicture,
                'showEmployeeNo' => $showEmployeeNo,
                'showName' => $showName,
                'desensitiseEmployeeNo' => $desensitiseEmployeeNo,
                'desensitiseName' => $desensitiseName,
                'uploadVerificationPic' => $uploadVerificationPic,
                'saveVerificationPic' => $saveVerificationPic,
                'saveFacePic' => $saveFacePic,
            ],
        ];

        $request = self::sendRequest($checkpoint, 'PUT', '/ISAPI/AccessControl/AcsCfg?format=json', $data);

        return new CheckpointResponseDto($request, $request->ok(), $request->body());
    }

    /**
     * Retrieves the system host notification capability of a given checkpoint.
     *
     * @param Checkpoint $checkpoint The checkpoint to retrieve the system time capability from.
     * @return CheckpointResponseDto The response DTO containing the system time capability.
     */
    public static function capabilityDeviceHttpHostNotification(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $request = self::createRequest($checkpoint)->get('/ISAPI/Event/notification/httpHosts/capabilities');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    /**
     * Create the HTTP host notification for a device.
     *
     * @param Checkpoint $checkpoint The Checkpoint object.
     * @param string $url The URL of the HTTP host notification.
     * @return CheckpointResponseDto The response from the server.
     */
    public static function createDeviceHttpHostNotification(Checkpoint $checkpoint, string $url, int $eventId = 1)
    {
        $urlParts = parse_url($url);

        $protocol = strtoupper($urlParts['scheme']);
        $host = $urlParts['host'];
        $path = $urlParts['path'] ?? null;
        $isIp = filter_var($host, FILTER_VALIDATE_IP);
        $port = $urlParts['port'] ?? 80;

        $xmlIpOrHost = ($isIp ? '<ipAddress>' . $host . '</ipAddress>' : '<hostName>' . $host . '</hostName>');
        $addresingFormatType = $isIp ? 'ipaddress' : 'hostname';

        $xmlData = <<<XML
            <HttpHostNotificationList version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
                <HttpHostNotification version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
                    <id>{$eventId}</id>
                    <url>{$path}</url>
                    <protocolType>{$protocol}</protocolType>
                    <parameterFormatType>XML</parameterFormatType>
                    <addressingFormatType>{$addresingFormatType}</addressingFormatType>
                    {$xmlIpOrHost}
                    <portNo>{$port}</portNo>
                    <httpAuthenticationMethod>none</httpAuthenticationMethod>
                    <SubscribeEvent>
                        <heartbeat>30</heartbeat>
                        <eventMode>all</eventMode>
                    </SubscribeEvent>
                </HttpHostNotification>
            </HttpHostNotificationList>
        XML;

        $request = self::createRequest($checkpoint)->withBody($xmlData, 'text/xml')->post('/ISAPI/Event/notification/httpHosts');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    /**
     * Updates the HTTP host notification for a device.
     *
     * @param Checkpoint $checkpoint The Checkpoint object.
     * @param string $url The URL of the HTTP host notification.
     * @param int $eventId The ID of the device to update.
     * @return CheckpointResponseDto The response from the server.
     */
    public static function updateDeviceHttpHostNotification(Checkpoint $checkpoint, string $url, int $eventId = 1)
    {
        $urlParts = parse_url($url);

        $protocol = strtoupper($urlParts['scheme']);
        $host = $urlParts['host'];
        $path = $urlParts['path'] ?? null;
        $isIp = filter_var($host, FILTER_VALIDATE_IP);
        $port = $urlParts['port'] ?? ($protocol === 'HTTPS' ? 443 : 80);

        $xmlIpOrHost = ($isIp ? '<ipAddress>' . $host . '</ipAddress>' : '<hostName>' . $host . '</hostName>');
        $addresingFormatType = $isIp ? 'ipaddress' : 'hostname';

        $xmlData = <<<XML
            <HttpHostNotificationList version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
                <HttpHostNotification version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
                    <id>{$eventId}</id>
                    <url>{$path}</url>
                    <protocolType>{$protocol}</protocolType>
                    <parameterFormatType>XML</parameterFormatType>
                    <addressingFormatType>{$addresingFormatType}</addressingFormatType>
                    {$xmlIpOrHost}
                    <portNo>{$port}</portNo>
                    <httpAuthenticationMethod>none</httpAuthenticationMethod>
                    <SubscribeEvent>
                        <heartbeat>30</heartbeat>
                        <eventMode>all</eventMode>
                    </SubscribeEvent>
                </HttpHostNotification>
            </HttpHostNotificationList>
        XML;

        $request = self::createRequest($checkpoint)->withBody($xmlData, 'text/xml')->put('/ISAPI/Event/notification/httpHosts');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    /**
     * Retrieves the NTP server for a given device.
     *
     * @param Checkpoint $checkpoint The checkpoint object.
     * @return CheckpointResponseDto The DTO object containing the response.
     */
    public static function getDeviceNTPServer(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $request = self::createRequest($checkpoint)->get('/ISAPI/System/time/ntpServers/1');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    /**
     * Updates the NTP server configuration for a given Checkpoint.
     *
     * @param Checkpoint $checkpoint The Checkpoint object.
     * @param string $hostName The hostname of the NTP server.
     * @param int $port The port number of the NTP server.
     * @param int $synchronizeInterval The synchronization interval in seconds. Default is 180 seconds.
     * @return CheckpointResponseDto The response from the Checkpoint API.
     */
    public static function updateDeviceNTPServer(Checkpoint $checkpoint, string $hostName, int $port, int $synchronizeInterval = 180): CheckpointResponseDto
    {
        $xmlData = <<<XML
        <NTPServer version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
            <id>1</id>
            <addressingFormatType>hostname</addressingFormatType>
            <hostName>{$hostName}</hostName>
            <portNo>{$port}</portNo>
            <synchronizeInterval>{$synchronizeInterval}</synchronizeInterval>
        </NTPServer>
        XML;

        $request = self::createRequest($checkpoint)->withBody($xmlData, 'text/xml')->put('/ISAPI/System/time/ntpServers/1');

        return new CheckpointResponseDto($request, $request->ok(), simplexml_load_string($request->body()));
    }

    public static function rebootCheckpoint(Checkpoint $checkpoint): CheckpointResponseDto
    {
        $rebootCheckpoint = self::sendRequest($checkpoint, 'PUT', '/ISAPI/System/reboot');

        return new CheckpointResponseDto($rebootCheckpoint, $rebootCheckpoint->ok(), $rebootCheckpoint->body());
    }

    private static function storeImageToStorage(string $faceUrl): ?string
    {
        $path = 'biometrics/' . Str::uuid() . '.jpeg';

        $hasStored = Storage::put($path, base64_decode($faceUrl));

        if (!$hasStored) {
            return null;
        }


        return Storage::url($path);
    }
}
