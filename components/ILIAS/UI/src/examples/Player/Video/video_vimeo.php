<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Player\Video;

/**
 * ---
 * description: >
 *   Example for rendering a vimeo video player.
 *
 * expected output: >
 *   ILIAS shows a base video player with a vimeo video including all functions like stop/start icons. The subtitle
 *   symbol (CC) is missing. Clicking the Start/Stop symbol will start playing the video. Clicking the symbol again will
 *   stop the video.
 *
 *   In addition following functions have to be tested:
 *   - The video starts playing if clicking the start/stop symbol in the middle of the image. The video stops after another click.
 *   - The sound fades or raises if the volumes gets changed by using the volume control.
 *   - Clicking the full screen icon maximizes the video player to the size of the desktop size. Clicking ESC will diminish the video player.
 * ---
 */
function video_vimeo(): string
{
    global $DIC;
    $renderer = $DIC->ui()->renderer();
    $f = $DIC->ui()->factory();

    $video = $f->player()->video("https://player.vimeo.com/video/669475821");

    return $renderer->render($video);
}
