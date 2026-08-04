<?php
namespace App\Enums;
enum ShipmentStatus: string { case Pending = 'pending'; case Ready = 'ready'; case InTransit = 'in_transit'; case Delivered = 'delivered'; case Exception = 'exception'; case Returned = 'returned'; }
