This class is designed to configure the environment for each PHP page by looking at the environment variables passed through apache conf.

The only variable it actively does something with (at this point) is the PHP_INCLUDE_PATH variable, which it uses to set the include_path for php.

Can be extended further in the future.

