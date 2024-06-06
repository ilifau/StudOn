<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Dropzone\File\Wrapper;

function with_clear_button()
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
<<<<<<< HEAD
=======
    $request = $DIC->http()->request();

    $submit_flag = 'dropzone_wrapper_with_clear_button';
    $post_url = "{$request->getUri()}&$submit_flag";
>>>>>>> v9.1

    $dropzone = $factory
        ->dropzone()->file()->wrapper(
            'Upload your files here',
<<<<<<< HEAD
            '#',
=======
            $post_url,
>>>>>>> v9.1
            $factory->messageBox()->info('Drag and drop files onto me!'),
            $factory->input()->field()->file(
                new \ilUIAsyncDemoFileUploadHandlerGUI(),
                'Your files'
            )
        );

    $dropzone = $dropzone->withActionButtons([
        $factory->button()->standard('Clear files!', '#')->withOnClick($dropzone->getClearSignal())
    ]);

    return $renderer->render($dropzone);
}
