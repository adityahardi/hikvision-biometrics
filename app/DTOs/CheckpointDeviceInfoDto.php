<?php

namespace App\DTOs;

class CheckpointDeviceInfoDto
{
    public string $deviceName;

    public int $deviceID;

    public string $model;

    public string $serialNumber;

    public string $macAddress;

    public string $firmwareVersion;

    public string $firmwareReleasedDate;

    public string $encoderVersion;

    public string $encoderReleasedDate;

    public string $deviceType;

    public int $telecontrolID;

    public bool $supportBeep;

    public int $localZoneNum;

    public int $alarmOutNum;

    public int $electroLockNum;

    public int $RS485Num;

    public string $manufacturer;

    public string $OEMCode;

    public string $marketType;

    public function __construct(
        string $deviceName,
        int $deviceID,
        string $model,
        string $serialNumber,
        string $macAddress,
        string $firmwareVersion,
        string $firmwareReleasedDate,
        string $encoderVersion,
        string $encoderReleasedDate,
        string $deviceType,
        int $telecontrolID,
        bool $supportBeep,
        int $localZoneNum,
        int $alarmOutNum,
        int $electroLockNum,
        int $RS485Num,
        string $manufacturer,
        string $OEMCode,
        string $marketType,
    ) {
        $this->deviceName = $deviceName;
        $this->deviceID = $deviceID;
        $this->model = $model;
        $this->serialNumber = $serialNumber;
        $this->macAddress = $macAddress;
        $this->firmwareVersion = $firmwareVersion;
        $this->firmwareReleasedDate = $firmwareReleasedDate;
        $this->encoderVersion = $encoderVersion;
        $this->encoderReleasedDate = $encoderReleasedDate;
        $this->deviceType = $deviceType;
        $this->telecontrolID = $telecontrolID;
        $this->supportBeep = $supportBeep;
        $this->localZoneNum = $localZoneNum;
        $this->alarmOutNum = $alarmOutNum;
        $this->electroLockNum = $electroLockNum;
        $this->RS485Num = $RS485Num;
        $this->manufacturer = $manufacturer;
        $this->OEMCode = $OEMCode;
        $this->marketType = $marketType;
    }

    public function getXML(): string
    {
        return <<<XML
        <DeviceInfo version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
            <deviceName>{$this->deviceName}</deviceName>
            <deviceID>{$this->deviceID}</deviceID>
            <model>{$this->model}</model>
            <serialNumber>{$this->serialNumber}</serialNumber>
            <macAddress>{$this->macAddress}</macAddress>
            <firmwareVersion>{$this->firmwareVersion}</firmwareVersion>
            <firmwareReleasedDate>{$this->firmwareReleasedDate}</firmwareReleasedDate>
            <encoderVersion>{$this->encoderVersion}</encoderVersion>
            <encoderReleasedDate>{$this->encoderReleasedDate}</encoderReleasedDate>
            <deviceType>{$this->deviceType}</deviceType>
            <telecontrolID>{$this->telecontrolID}</telecontrolID>
            <supportBeep>{$this->supportBeep}</supportBeep>
            <localZoneNum>{$this->localZoneNum}</localZoneNum>
            <alarmOutNum>{$this->alarmOutNum}</alarmOutNum>
            <electroLockNum>{$this->electroLockNum}</electroLockNum>
            <RS485Num>{$this->RS485Num}</RS485Num>
            <manufacturer>{$this->manufacturer}</manufacturer>
            <OEMCode>{$this->OEMCode}</OEMCode>
            <marketType>{$this->marketType}</marketType>
        </DeviceInfo>
        XML;
    }
}
