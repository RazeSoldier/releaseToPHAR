<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @copyright
 */

define( 'TMPDIR', 'tmp/' );
define( 'PHAR_NAME', 'release.phar' );

// Config @{
$projectURL = ''; // The git remote address
$composerPath = ''; // Manually specify Composer's execution file, you can also leave blank
$bootstrapFilename = ''; // Set bootstrap of the PHAR file
// @}

register_shutdown_function( 'shutdown' );
echo "Downloading source code...\n";
getSourceCode($projectURL);
chdir( TMPDIR );
if ( installLib( $composerPath ) !== 0 ) {
    throw new RuntimeException( 'Unable to install dependencies', 2 );
}
echo "Start make PHAR file\n";
makePHAR( PHAR_NAME, $bootstrapFilename );
if ( !file_exists( PHAR_NAME ) ) {
    trigger_error( "Build failed, unknown error.\n" , E_USER_ERROR);
} else {
    rename( PHAR_NAME, '../' . PHAR_NAME );
    echo 'Build successfully, saved as ' . PHAR_NAME . "\n";
}
chdir( '..' );

function getSourceCode($url)
{
    system( "git clone $url tmp/" );
}

function installLib($composerPath = null)
{
    if ( !file_exists( 'composer.json' ) ) {
        return;
    }
    if ( !empty( $composerPath ) ) {
        if ( !file_exists( $composerPath ) ) {
            throw new RuntimeException( "$composerPath does not exists", 1 );
        }
        $command = "php $composerPath install --no-dev";
        return;
    } else {
        $command = 'composer install --no-dev';
    }

    system( $command, $code );
    return $code;
}

function makePHAR($pharName, $bootstrapFilename)
{
    try {
        $phar = new Phar( $pharName );
        $phar->buildFromDirectory( '.' );
        // Set phar file head
        $pharHead = "#!/usr/bin/env php\n";
        $phar->setStub( $pharHead . $phar->createDefaultStub( $bootstrapFilename ) );
    } catch ( PharException $e ) {
        echo 'Write operations failed on brandnewphar.phar: ', $e;
    } catch ( UnexpectedValueException $e ) {
        if ( ini_get( 'phar.readonly' ) == 1 ) {
            $errorMsg = "phar.readonly is set to 1, build script does not work. (Please set phar.readonly to 0 in php.ini)\n";
            trigger_error( $errorMsg , E_USER_ERROR );
        }
    } catch ( Exception $e ) {
        echo $e . "\n";
    }
}

function delDirTree($dir) { 
    $files = array_diff( scandir( $dir ), [ '.', '..' ] ); 
    foreach ( $files as $file ) { 
        is_dir( "$dir/$file" ) ? delDirTree( "$dir/$file" ) : unlink( "$dir/$file" ); 
    } 
    return rmdir( $dir ); 
}

function shutdown()
{
    delDirTree( TMPDIR );
}