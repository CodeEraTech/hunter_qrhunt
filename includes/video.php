<?php
declare(strict_types=1);

function youtube_embed_url(string $url): string
{
    $parts=parse_url(trim($url));$host=strtolower((string)($parts['host']??''));$id='';
    if(in_array($host,['youtu.be','www.youtu.be'],true))$id=trim((string)($parts['path']??''),'/');
    elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com'],true)){parse_str((string)($parts['query']??''),$query);$id=(string)($query['v']??'');if($id===''&&str_starts_with((string)($parts['path']??''),'/embed/'))$id=substr((string)$parts['path'],7);}
    if(!preg_match('/^[A-Za-z0-9_-]{6,20}$/',$id))throw new InvalidArgumentException('Only a valid YouTube URL is accepted.');
    return 'https://www.youtube-nocookie.com/embed/'.rawurlencode($id).'?controls=0&rel=0&modestbranding=1&playsinline=1';
}
