<?php

namespace viget\videoembed\enums;

enum VideoType: string
{
    case YOUTUBE = 'youtube';
    case VIMEO = 'vimeo';
    case UNKNOWN = 'unknown';
}