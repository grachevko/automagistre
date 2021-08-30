<?php

declare(strict_types=1);

return [
    App\Appeal\Entity\AppealId::class => ['appeal_id'],
    App\Calendar\Entity\CalendarEntryId::class => ['calendar_entry_id'],
    App\Car\Entity\CarId::class => ['car_id'],
    App\Car\Entity\RecommendationId::class => ['recommendation_id'],
    App\Car\Entity\RecommendationPartId::class => ['recommendation_part_id'],
    App\Customer\Entity\CustomerTransactionId::class => ['customer_transaction_id'],
    App\Customer\Entity\OperandId::class => ['operand_id'],
    App\Employee\Entity\EmployeeId::class => ['employee_id'],
    App\Employee\Entity\SalaryId::class => ['salary_id'],
    App\Expense\Entity\ExpenseId::class => ['expense_id'],
    App\Income\Entity\IncomeId::class => ['income_id'],
    App\Income\Entity\IncomePartId::class => ['income_part_id'],
    App\MC\Entity\McEquipmentId::class => ['mc_equipment_id'],
    App\MC\Entity\McWorkId::class => ['mc_work_id'],
    App\Manufacturer\Entity\ManufacturerId::class => ['manufacturer_id'],
    App\Order\Entity\OrderId::class => ['order_id'],
    App\Order\Entity\ReservationId::class => ['reservation_id'],
    App\Part\Entity\PartCaseId::class => ['part_case_id'],
    App\Part\Entity\PartId::class => ['part_id'],
    App\Review\Entity\ReviewId::class => ['review_id'],
    App\Sms\Entity\SmsId::class => ['sms_id'],
    App\Storage\Entity\InventorizationId::class => ['inventorization_id'],
    App\Storage\Entity\MotionId::class => ['motion_id'],
    App\Storage\Entity\WarehouseId::class => ['warehouse_id'],
    App\User\Entity\UserId::class => ['user_id'],
    App\Vehicle\Entity\VehicleId::class => ['vehicle_id'],
    App\Wallet\Entity\WalletId::class => ['wallet_id'],
    App\Wallet\Entity\WalletTransactionId::class => ['wallet_transaction_id'],
];
