<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Dropzone\File\Wrapper;

function base()
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();
<<<<<<< HEAD
=======
    $wrapper = $DIC->http()->wrapper()->query();

    $submit_flag = 'dropzone_wrapper_base';
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

    // please use ilCtrl to generate an appropriate link target
    // and check it's command instead of this.
<<<<<<< HEAD
    if ('POST' === $request->getMethod()) {
=======
    if ($wrapper->has($submit_flag)) {
>>>>>>> v9.1
        $dropzone = $dropzone->withRequest($request);
        $data = $dropzone->getData();
    } else {
        $data = 'no results yet.';
    }

    return '<pre>' . print_r($data, true) . '</pre>' .
        $renderer->render($dropzone);
}
