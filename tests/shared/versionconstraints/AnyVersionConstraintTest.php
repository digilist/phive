<?php
namespace PharIo\Phive {

    class AnyVersionConstraintTest extends \PHPUnit_Framework_TestCase {

        /**
         * @dataProvider versionProvider
         *
         * @param Version $version
         */
        public function testReturnsTrue(Version $version) {
            $constraint = new AnyVersionConstraint();
            $this->assertTrue($constraint->complies($version));
        }

        public static function versionProvider() {
            return [
                [new Version('1.0.2')],
                [new Version('4.8')],
                [new Version('0.1.1-dev')]
            ];
        }

    }

}