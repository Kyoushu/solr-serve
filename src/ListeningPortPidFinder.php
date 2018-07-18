<?php

namespace Kyoushu\SolrServe;

class ListeningPortPidFinder
{

    const REGEX_LSOF_LINE = '/^(?<command>[^\s]+)\s+(?<pid>[0-9]+)\s+(?<user>[^\s]+)\s+(?<fd>[^\s]+)\s+(?<type>[^\s]+)\s+(?<device>[^\s]+)\s+(?<size_off>[^\s]+)\s+(?<node>[^\s]+)\s+(?<addr>[^:]+):(?<port>[0-9]+) \(LISTEN\)/';

    public static function findPidByListeningPort(int $port): ?int
    {
        foreach(self::getListeningPorts() as $result){
            if($result['port'] === $port) return $result['pid'];
        }
        return null;
    }

    public static function getListeningPorts(): array
    {
        exec('lsof -i', $output);
        $results = [];
        foreach($output as $line){
            if(!preg_match(self::REGEX_LSOF_LINE, $line, $match)) continue;
            $results[] = [
                'command' => $match['command'],
                'pid' => (int)$match['pid'],
                'user' => $match['user'],
                'fd' => $match['fd'],
                'type' => $match['type'],
                'device' => $match['device'],
                'size_off' => $match['size_off'],
                'node' => $match['node'],
                'addr' => $match['addr'],
                'port' => (int)$match['port']
            ];
        }
        return $results;
    }

}