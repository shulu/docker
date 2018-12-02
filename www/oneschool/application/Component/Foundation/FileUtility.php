<?php
namespace Lychee\Component\Foundation;

class FileUtility {

    /**
     * 格式化文件大小
     * @param int  字节数
     *
     * @return string   xxx KB, XX GB ... 
     */
    public function formatSize( $bytes ) {
        $types = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
        for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $types[$i] );
    }
} 