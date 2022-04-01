<?php

namespace Apsonex\Media\Factory;

/**
 * @url https://express.adobe.com/tools/image-resize
 * @url https://www.adobe.com/express/feature/image/resize
 */
enum ImageSize: string
{

    /**
     * General
     */
    const hd = '1920x1080';
    const thumbnail = '100x100';
    const thumbnailLarge = '600x600';

    /**
     * Ratios
     */
    const wideRatio = '16:9';
    const iphoneRatio = '9:16';
    const presentationSlideRatio = '4:3';
    const squareRatio = '1:1';
    const landscapeRatio = '3:2';
    const portraitRatio = '2:3';

    /**
     * Twitter Post
     */
    const twitterPost = '1200x670|16:9';

    /**
     * Youtube
     */
    const youtubeThumbnail = '1280x720|16:9';

    /**
     * Pinterest
     */
    const pinterestPin = '735x1102|2:3';

    /**
     * Linkedin
     */
    const linkedinSharedPost = '1200x628|1.91:1';

    /**
     * Snapchat
     */
    const snapchatStory = '1080x1920|9:16';

    /**
     * Facebook
     */
    const facebookPost = '1200x630|1.91:1';

    /**
     * Instagram
     */
    const instagramStory = '1080x1920|9:16';
    const instagramSquare = '1080x1080|1:1';
    const instagramPortrait = '1080x1350|4:5';
    const instagramLandscape = '1080x566|1.91:1';
}
