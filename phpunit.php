<?php
require $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

if (!file_exists(__DIR__ . '/screenshot'))
{
    mkdir(__DIR__ . '/screenshot');
}

class TestRunner extends \PHPUnit_TextUI_TestRunner
{
    public static $currentTest;

    /**
     * {@inheritdoc}
     */
    protected function handleConfiguration(array &$arguments)
    {
        $listener = new Class extends \PHPUnit_Framework_BaseTestListener
        {
            public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
            {
                $this->screen($test);
            }

            public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
            {
                $this->screen($test);
            }

            private function screen(PHPUnit_Framework_Test $test)
            {
                $dir = sys_get_temp_dir();
                $file = $dir . DIRECTORY_SEPARATOR . uniqid().'.html';

                $reflectionClass = new ReflectionClass($test);
                if($reflectionClass->hasProperty('client')) {
                    $reflClient = $reflectionClass->getProperty('client');
                    $reflClient->setAccessible(true);
                    $client = $reflClient->getValue($test);
                    if (null !== $client->getResponse())
                    {
                        file_put_contents($file, $client->getResponse()->getContent());
                        exec('google-chrome --headless --window-size=1280,1696 --screenshot file://'.$file. ' 2> /dev/null');
                        rename('screenshot.png', 'screenshot/screenshot-'.str_replace('/', '_', $client->getRequest()->getPathInfo()).'.png');
                    }
                }
            }
        };

        $result = parent::handleConfiguration($arguments);
        $arguments['listeners'] = isset($arguments['listeners']) ? $arguments['listeners'] : array();
        $registeredLocally = false;

        if (!$registeredLocally) {
            $arguments['listeners'][] = $listener;
        }
        return $result;
    }
}

class Command extends \PHPUnit_TextUI_Command
{
    protected function createRunner()
    {
        return new TestRunner($this->arguments['loader']);
    }
}

Command::main(true);