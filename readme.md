# Initial Setup

## Register the service in the app config
Square1\Laravel\Connect\ConnectServiceProvider::class

## Replace the Schema Facade 
Open  config/app.php look for:
'Schema' => Illuminate\Support\Facades\Schema::class, and replace with 
'Schema' => Square1\Laravel\Connect\Model\ConnectSchema::class,
