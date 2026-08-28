<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN ABOUT DETAILS
 * =========================================================
 *
 * File:
 * - admin/about-details.php
 *
 * Purpose:
 * - Manage About Us page content
 * - Edit brand story
 * - Edit social media links
 * - Upload/update About image or logo
 * - Display current content
 * - Save changes safely
 *
 * Existing project structure:
 *
 * ssrini-handcrafts-admin/
 *
 * ├── admin/
 * │   ├── about-details.php
 * │   ├── activity-logs.php
 * │   ├── filter-configuration.php
 * │   └── ...
 * │
 * ├── api/
 * ├── assets/
 * │   ├── css/
 * │   ├── js/
 * │   └── images/
 * │
 * ├── config/
 * │   └── database.php
 * │
 * ├── includes/
 * │   ├── auth.php
 * │   ├── sidebar.php
 * │   └── header.php
 * │
 * └── database/
 *
 * IMPORTANT:
 * - This page DOES NOT modify database structure.
 * - It only reads/writes the existing about_content table.
 * - Common column names are detected automatically.
 */


/**
 * =========================================================
 * DATABASE + AUTHENTICATION
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();


/**
 * =========================================================
 * PAGE CONFIGURATION
 * =========================================================
 */

$pageTitle = 'About Details';


/**
 * =========================================================
 * HELPER FUNCTIONS
 * =========================================================
 */


/**
 * Escape HTML safely.
 */
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Check whether a table exists.
 */
function tableExists(
    PDO $pdo,
    string $tableName
): bool {

    try {

        $stmt = $pdo->prepare(
            "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table_name
            "
        );

        $stmt->execute([
            ':table_name' => $tableName
        ]);

        return (int) $stmt->fetchColumn() > 0;

    } catch (Throwable $e) {

        return false;
    }
}


/**
 * Get all columns from a table.
 */
function getTableColumns(
    PDO $pdo,
    string $tableName
): array {

    if (
        !tableExists(
            $pdo,
            $tableName
        )
    ) {

        return [];
    }

    try {

        $safeTable =
            str_replace(
                '`',
                '``',
                $tableName
            );

        $stmt =
            $pdo->query(
                "SHOW COLUMNS FROM `{$safeTable}`"
            );

        $columns = [];

        while (
            $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )
        ) {

            if (
                isset(
                    $row['Field']
                )
            ) {

                $columns[] =
                    $row['Field'];
            }
        }

        return $columns;

    } catch (Throwable $e) {

        return [];
    }
}


/**
 * Find the first matching column.
 */
function findColumn(
    array $columns,
    array $candidates
): ?string {

    $lowerColumns = [];

    foreach (
        $columns
        as $column
    ) {

        $lowerColumns[
            strtolower(
                $column
            )
        ] = $column;
    }


    foreach (
        $candidates
        as $candidate
    ) {

        $key =
            strtolower(
                $candidate
            );

        if (
            isset(
                $lowerColumns[$key]
            )
        ) {

            return
                $lowerColumns[$key];
        }
    }


    return null;
}


/**
 * Quote database identifier safely.
 */
function quoteIdentifier(
    string $identifier
): string {

    return '`' .
        str_replace(
            '`',
            '``',
            $identifier
        ) .
        '`';
}


/**
 * Get POST value.
 */
function postValue(
    string $key,
    string $default = ''
): string {

    return isset(
        $_POST[$key]
    )
        ? trim(
            (string) $_POST[$key]
        )
        : $default;
}


/**
 * Get first character for avatar.
 */
function getInitial(
    string $value
): string {

    $value =
        trim(
            $value
        );

    if (
        $value === ''
    ) {

        return 'A';
    }

    return strtoupper(
        substr(
            $value,
            0,
            1
        )
    );
}


/**
 * Validate URL.
 */
function isValidUrl(
    string $url
): bool {

    if (
        $url === ''
    ) {

        return true;
    }

    return filter_var(
        $url,
        FILTER_VALIDATE_URL
    ) !== false;
}


/**
 * Format date/time.
 */
function formatDateTime(
    $value
): string {

    if (
        empty($value)
    ) {

        return '—';
    }

    $timestamp =
        strtotime(
            (string) $value
        );

    if (
        $timestamp === false
    ) {

        return (string) $value;
    }

    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


/**
 * =========================================================
 * DEFAULT CONTENT
 * =========================================================
 *
 * These values are used when the database is empty.
 */

$defaultAbout = [

    'title' =>
        'About Ssrini',

    'subtitle' =>
        'Creative handcrafted products from Bengal.',

    'content' =>
        "Welcome to Ssrini, the brand of Adrish Creative. Our story began in Kolkata in 2004 with an institute dedicated to the art of handicrafts. By 2021, we expanded, opening our first manufacturing unit to create and share the beautiful work of Bengal's artisans with the world.\n\nWe specialize in a wide range of handcrafted items—from designer kurtis and blouses to handmade jewellery and home decor. Our mission is to empower women artisans in Bengal, giving them a global platform for their talent. Every product you purchase directly supports this community and helps preserve the rich legacy of Bengal's handicraft.\n\nEach piece is a work of art, made with care and delivered right to your doorstep.\n\nDiscover authentic, handcrafted products from anywhere in India and soon, around the world.",

    'image' =>
        '',

    'instagram' =>
        'https://www.instagram.com/ssrini.handicrafts?igsi=MWNlbXY3eW11MDE4dw==',

    'twitter' =>
        'https://x.com/SsriniHandies',

    'facebook' =>
        'https://www.facebook.com/share/1DPgwDFBj2/',

    'updated_at' =>
        ''

];


/**
 * =========================================================
 * DEFAULT VARIABLES
 * =========================================================
 */

$aboutData =
    $defaultAbout;

$aboutTableExists =
    false;

$aboutError =
    null;

$successMessage =
    null;

$warningMessage =
    null;

$aboutColumns =
    [];

$recordId =
    null;


/**
 * =========================================================
 * MESSAGE FROM SESSION
 * =========================================================
 */

if (
    isset(
        $_SESSION['about_success']
    )
) {

    $successMessage =
        (string)
        $_SESSION[
            'about_success'
        ];

    unset(
        $_SESSION[
            'about_success'
        ]
    );
}


if (
    isset(
        $_SESSION['about_error']
    )
) {

    $aboutError =
        (string)
        $_SESSION[
            'about_error'
        ];

    unset(
        $_SESSION[
            'about_error'
        ]
    );
}


/**
 * =========================================================
 * DATABASE ANALYSIS
 * =========================================================
 */

try {

    /**
     * -----------------------------------------------------
     * CHECK TABLE
     * -----------------------------------------------------
     */

    $aboutTableExists =
        tableExists(
            $pdo,
            'about_content'
        );


    if (
        !$aboutTableExists
    ) {

        $aboutError =
            'The about_content table was not found in the current database.';

    } else {

        /**
         * -------------------------------------------------
         * GET COLUMNS
         * -------------------------------------------------
         */

        $aboutColumns =
            getTableColumns(
                $pdo,
                'about_content'
            );


        /**
         * -------------------------------------------------
         * FIND COMMON COLUMNS
         * -------------------------------------------------
         */

        $idColumn =
            findColumn(
                $aboutColumns,
                [
                    'id',
                    'about_id',
                    'content_id'
                ]
            );


        $titleColumn =
            findColumn(
                $aboutColumns,
                [
                    'title',
                    'heading',
                    'about_title',
                    'page_title'
                ]
            );


        $subtitleColumn =
            findColumn(
                $aboutColumns,
                [
                    'subtitle',
                    'sub_title',
                    'tagline',
                    'short_description'
                ]
            );


        $contentColumn =
            findColumn(
                $aboutColumns,
                [
                    'content',
                    'description',
                    'about_content',
                    'about_description',
                    'story',
                    'details',
                    'body'
                ]
            );


        $imageColumn =
            findColumn(
                $aboutColumns,
                [
                    'image',
                    'image_path',
                    'about_image',
                    'about_image_path',
                    'logo',
                    'logo_image'
                ]
            );


        $instagramColumn =
            findColumn(
                $aboutColumns,
                [
                    'instagram',
                    'instagram_url',
                    'instagram_link'
                ]
            );


        $twitterColumn =
            findColumn(
                $aboutColumns,
                [
                    'twitter',
                    'twitter_url',
                    'twitter_link',
                    'x_url',
                    'x_link'
                ]
            );


        $facebookColumn =
            findColumn(
                $aboutColumns,
                [
                    'facebook',
                    'facebook_url',
                    'facebook_link'
                ]
            );


        $updatedAtColumn =
            findColumn(
                $aboutColumns,
                [
                    'updated_at',
                    'updated_date',
                    'modified_at',
                    'last_updated'
                ]
            );


        /**
         * -------------------------------------------------
         * FETCH EXISTING RECORD
         * -------------------------------------------------
         */

        $orderColumn =
            $idColumn !== null
                ? quoteIdentifier(
                    $idColumn
                )
                : '1';


        $selectColumns =
            [];


        foreach (
            $aboutColumns
            as $column
        ) {

            $selectColumns[] =
                quoteIdentifier(
                    $column
                );
        }


        if (
            !empty(
                $selectColumns
            )
        ) {

            $selectSQL =
                implode(
                    ', ',
                    $selectColumns
                );


            $stmt =
                $pdo->query(
                    "
                    SELECT
                        {$selectSQL}

                    FROM
                        about_content

                    ORDER BY
                        {$orderColumn}
                        ASC

                    LIMIT 1
                    "
                );


            $existingData =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                $existingData !== false
            ) {

                /**
                 * Store ID.
                 */

                if (
                    $idColumn !== null &&
                    isset(
                        $existingData[
                            $idColumn
                        ]
                    )
                ) {

                    $recordId =
                        $existingData[
                            $idColumn
                        ];
                }


                /**
                 * Title.
                 */

                if (
                    $titleColumn !== null
                ) {

                    $aboutData['title'] =
                        (string)
                        (
                            $existingData[
                                $titleColumn
                            ] ?? ''
                        );
                }


                /**
                 * Subtitle.
                 */

                if (
                    $subtitleColumn !== null
                ) {

                    $aboutData['subtitle'] =
                        (string)
                        (
                            $existingData[
                                $subtitleColumn
                            ] ?? ''
                        );
                }


                /**
                 * Content.
                 */

                if (
                    $contentColumn !== null
                ) {

                    $aboutData['content'] =
                        (string)
                        (
                            $existingData[
                                $contentColumn
                            ] ?? ''
                        );
                }


                /**
                 * Image.
                 */

                if (
                    $imageColumn !== null
                ) {

                    $aboutData['image'] =
                        (string)
                        (
                            $existingData[
                                $imageColumn
                            ] ?? ''
                        );
                }


                /**
                 * Instagram.
                 */

                if (
                    $instagramColumn !== null
                ) {

                    $aboutData['instagram'] =
                        (string)
                        (
                            $existingData[
                                $instagramColumn
                            ] ?? ''
                        );
                }


                /**
                 * Twitter / X.
                 */

                if (
                    $twitterColumn !== null
                ) {

                    $aboutData['twitter'] =
                        (string)
                        (
                            $existingData[
                                $twitterColumn
                            ] ?? ''
                        );
                }


                /**
                 * Facebook.
                 */

                if (
                    $facebookColumn !== null
                ) {

                    $aboutData['facebook'] =
                        (string)
                        (
                            $existingData[
                                $facebookColumn
                            ] ?? ''
                        );
                }


                /**
                 * Updated at.
                 */

                if (
                    $updatedAtColumn !== null
                ) {

                    $aboutData['updated_at'] =
                        (string)
                        (
                            $existingData[
                                $updatedAtColumn
                            ] ?? ''
                        );
                }
            }
        }


        /**
         * -------------------------------------------------
         * FALLBACK WARNING
         * -------------------------------------------------
         */

        if (
            $titleColumn === null &&
            $subtitleColumn === null &&
            $contentColumn === null &&
            $imageColumn === null
        ) {

            $warningMessage =
                'The about_content table exists, but its columns could not be recognised automatically.';

        }
    }

} catch (Throwable $e) {

    $aboutError =
        'About details could not be loaded. Please check your database configuration.';
}


/**
 * =========================================================
 * SAVE FORM
 * =========================================================
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $aboutTableExists
) {

    try {

        /**
         * -------------------------------------------------
         * CSRF TOKEN
         * -------------------------------------------------
         *
         * Create token if authentication system does not
         * already provide one.
         */

        if (
            !isset(
                $_SESSION['about_csrf_token']
            )
        ) {

            $_SESSION[
                'about_csrf_token'
            ] =
                bin2hex(
                    random_bytes(32)
                );
        }


        $postedToken =
            isset(
                $_POST['csrf_token']
            )
                ? (string)
                $_POST['csrf_token']
                : '';


        if (
            !hash_equals(
                (string)
                $_SESSION[
                    'about_csrf_token'
                ],
                $postedToken
            )
        ) {

            throw new RuntimeException(
                'Invalid security token. Please refresh the page and try again.'
            );
        }


        /**
         * -------------------------------------------------
         * GET FORM VALUES
         * -------------------------------------------------
         */

        $newTitle =
            postValue(
                'title'
            );


        $newSubtitle =
            postValue(
                'subtitle'
            );


        $newContent =
            isset(
                $_POST['content']
            )
                ? trim(
                    (string)
                    $_POST['content']
                )
                : '';


        $newInstagram =
            postValue(
                'instagram'
            );


        $newTwitter =
            postValue(
                'twitter'
            );


        $newFacebook =
            postValue(
                'facebook'
            );


        /**
         * -------------------------------------------------
         * VALIDATION
         * -------------------------------------------------
         */

        if (
            $newTitle === ''
        ) {

            throw new RuntimeException(
                'About page title is required.'
            );
        }


        if (
            $newContent === ''
        ) {

            throw new RuntimeException(
                'About page description/story is required.'
            );
        }


        if (
            !isValidUrl(
                $newInstagram
            )
        ) {

            throw new RuntimeException(
                'Please enter a valid Instagram URL.'
            );
        }


        if (
            !isValidUrl(
                $newTwitter
            )
        ) {

            throw new RuntimeException(
                'Please enter a valid X/Twitter URL.'
            );
        }


        if (
            !isValidUrl(
                $newFacebook
            )
        ) {

            throw new RuntimeException(
                'Please enter a valid Facebook URL.'
            );
        }


        /**
         * -------------------------------------------------
         * IMAGE UPLOAD
         * -------------------------------------------------
         */

        $uploadedImagePath =
            $aboutData['image'];


        if (
            isset(
                $_FILES['about_image']
            ) &&
            isset(
                $_FILES['about_image']['error']
            ) &&
            $_FILES[
                'about_image'
            ]['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $file =
                $_FILES[
                    'about_image'
                ];


            if (
                $file['error'] !==
                UPLOAD_ERR_OK
            ) {

                throw new RuntimeException(
                    'The image upload failed.'
                );
            }


            /**
             * Maximum file size:
             * 5 MB
             */

            $maxFileSize =
                5 * 1024 * 1024;


            if (
                (int)
                $file['size'] >
                $maxFileSize
            ) {

                throw new RuntimeException(
                    'Image size must be 5 MB or less.'
                );
            }


            /**
             * Validate MIME type.
             */

            $allowedMimeTypes = [

                'image/jpeg' =>
                    'jpg',

                'image/png' =>
                    'png',

                'image/webp' =>
                    'webp'

            ];


            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );


            $mimeType =
                $finfo->file(
                    $file['tmp_name']
                );


            if (
                !isset(
                    $allowedMimeTypes[
                        $mimeType
                    ]
                )
            ) {

                throw new RuntimeException(
                    'Only JPG, PNG and WEBP images are allowed.'
                );
            }


            /**
             * Verify actual image.
             */

            if (
                @getimagesize(
                    $file['tmp_name']
                ) === false
            ) {

                throw new RuntimeException(
                    'The uploaded file is not a valid image.'
                );
            }


            /**
             * Upload directory.
             */

            $uploadDirectory =
                __DIR__ .
                '/../assets/images/about/';


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'Unable to create the About image directory.'
                    );
                }
            }


            /**
             * Generate unique filename.
             */

            $extension =
                $allowedMimeTypes[
                    $mimeType
                ];


            $fileName =
                'about-' .
                date(
                    'YmdHis'
                ) .
                '-' .
                bin2hex(
                    random_bytes(4)
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDirectory .
                $fileName;


            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )
            ) {

                throw new RuntimeException(
                    'Unable to save the uploaded image.'
                );
            }


            /**
             * Database-relative path.
             */

            $uploadedImagePath =
                'assets/images/about/' .
                $fileName;
        }


        /**
         * -------------------------------------------------
         * PREPARE UPDATE DATA
         * -------------------------------------------------
         */

        $updateData = [];


        if (
            $titleColumn !== null
        ) {

            $updateData[
                $titleColumn
            ] =
                $newTitle;
        }


        if (
            $subtitleColumn !== null
        ) {

            $updateData[
                $subtitleColumn
            ] =
                $newSubtitle;
        }


        if (
            $contentColumn !== null
        ) {

            $updateData[
                $contentColumn
            ] =
                $newContent;
        }


        if (
            $imageColumn !== null
        ) {

            $updateData[
                $imageColumn
            ] =
                $uploadedImagePath;
        }


        if (
            $instagramColumn !== null
        ) {

            $updateData[
                $instagramColumn
            ] =
                $newInstagram;
        }


        if (
            $twitterColumn !== null
        ) {

            $updateData[
                $twitterColumn
            ] =
                $newTwitter;
        }


        if (
            $facebookColumn !== null
        ) {

            $updateData[
                $facebookColumn
            ] =
                $newFacebook;
        }


        /**
         * Updated timestamp.
         */

        if (
            $updatedAtColumn !== null
        ) {

            $updateData[
                $updatedAtColumn
            ] =
                date(
                    'Y-m-d H:i:s'
                );
        }


        /**
         * -------------------------------------------------
         * MAKE SURE THERE IS SOMETHING TO SAVE
         * -------------------------------------------------
         */

        if (
            empty(
                $updateData
            )
        ) {

            throw new RuntimeException(
                'No compatible columns were found for saving About details.'
            );
        }


        /**
         * -------------------------------------------------
         * INSERT OR UPDATE
         * -------------------------------------------------
         */

        if (
            $recordId !== null &&
            $idColumn !== null
        ) {

            /**
             * UPDATE existing record.
             */

            $setParts =
                [];


            $params =
                [];


            foreach (
                $updateData
                as $column =>
                $value
            ) {

                $parameter =
                    ':set_' .
                    count(
                        $params
                    );


                $setParts[] =
                    quoteIdentifier(
                        $column
                    ) .
                    ' = ' .
                    $parameter;


                $params[
                    $parameter
                ] =
                    $value;
            }


            $params[
                ':record_id'
            ] =
                $recordId;


            $sql =
                "
                UPDATE about_content
                SET
                    " .
                implode(
                    ",\n",
                    $setParts
                ) .
                "
                WHERE " .
                quoteIdentifier(
                    $idColumn
                ) .
                " = :record_id
                LIMIT 1
                ";


            $stmt =
                $pdo->prepare(
                    $sql
                );


            $stmt->execute(
                $params
            );

        } else {

            /**
             * INSERT new record.
             */

            $columnsSQL =
                [];


            $valuesSQL =
                [];


            $params =
                [];


            foreach (
                $updateData
                as $column =>
                $value
            ) {

                $parameter =
                    ':value_' .
                    count(
                        $params
                    );


                $columnsSQL[] =
                    quoteIdentifier(
                        $column
                    );


                $valuesSQL[] =
                    $parameter;


                $params[
                    $parameter
                ] =
                    $value;
            }


            $sql =
                "
                INSERT INTO about_content
                (
                    " .
                implode(
                    ', ',
                    $columnsSQL
                ) .
                "
                )
                VALUES
                (
                    " .
                implode(
                    ', ',
                    $valuesSQL
                ) .
                "
                )
                ";


            $stmt =
                $pdo->prepare(
                    $sql
                );


            $stmt->execute(
                $params
            );


            if (
                $idColumn !== null
            ) {

                $recordId =
                    $pdo->lastInsertId();
            }
        }


        /**
         * -------------------------------------------------
         * ACTIVITY LOG
         * -------------------------------------------------
         *
         * Log only when a compatible activity_logs table
         * exists. This will not break the page if the table
         * is unavailable.
         */

        try {

            if (
                tableExists(
                    $pdo,
                    'activity_logs'
                )
            ) {

                $activityColumns =
                    getTableColumns(
                        $pdo,
                        'activity_logs'
                    );


                $activityActionColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'action',
                            'activity',
                            'event',
                            'event_type',
                            'action_type'
                        ]
                    );


                $activityDescriptionColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'description',
                            'details',
                            'message',
                            'activity_description',
                            'log_message'
                        ]
                    );


                $activityAdminColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'admin_id',
                            'user_id',
                            'administrator_id'
                        ]
                    );


                $activityIpColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'ip_address',
                            'ip',
                            'client_ip'
                        ]
                    );


                $activityUserAgentColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'user_agent',
                            'browser',
                            'agent'
                        ]
                    );


                $activityCreatedColumn =
                    findColumn(
                        $activityColumns,
                        [
                            'created_at',
                            'created_date',
                            'logged_at',
                            'activity_date',
                            'date',
                            'timestamp'
                        ]
                    );


                $activityInsertColumns =
                    [];


                $activityInsertValues =
                    [];


                $activityParams =
                    [];


                if (
                    $activityActionColumn !== null
                ) {

                    $activityInsertColumns[] =
                        quoteIdentifier(
                            $activityActionColumn
                        );


                    $activityInsertValues[] =
                        ':activity_action';


                    $activityParams[
                        ':activity_action'
                    ] =
                        'update_about';
                }


                if (
                    $activityDescriptionColumn !== null
                ) {

                    $activityInsertColumns[] =
                        quoteIdentifier(
                            $activityDescriptionColumn
                        );


                    $activityInsertValues[] =
                        ':activity_description';


                    $activityParams[
                        ':activity_description'
                    ] =
                        'Updated About Details from admin panel.';
                }


                if (
                    $activityIpColumn !== null
                ) {

                    $activityInsertColumns[] =
                        quoteIdentifier(
                            $activityIpColumn
                        );


                    $activityInsertValues[] =
                        ':activity_ip';


                    $activityParams[
                        ':activity_ip'
                    ] =
                        $_SERVER[
                            'REMOTE_ADDR'
                        ] ??
                        '';
                }


                if (
                    $activityUserAgentColumn !== null
                ) {

                    $activityInsertColumns[] =
                        quoteIdentifier(
                            $activityUserAgentColumn
                        );


                    $activityInsertValues[] =
                        ':activity_user_agent';


                    $activityParams[
                        ':activity_user_agent'
                    ] =
                        $_SERVER[
                            'HTTP_USER_AGENT'
                        ] ??
                        '';
                }


                if (
                    $activityCreatedColumn !== null
                ) {

                    $activityInsertColumns[] =
                        quoteIdentifier(
                            $activityCreatedColumn
                        );


                    $activityInsertValues[] =
                        ':activity_created';


                    $activityParams[
                        ':activity_created'
                    ] =
                        date(
                            'Y-m-d H:i:s'
                        );
                }


                /**
                 * Try to get current admin ID.
                 */

                if (
                    $activityAdminColumn !== null
                ) {

                    $adminId =
                        $_SESSION['admin_id'] ??
                        $_SESSION['user_id'] ??
                        $_SESSION['administrator_id'] ??
                        null;


                    if (
                        $adminId !== null
                    ) {

                        $activityInsertColumns[] =
                            quoteIdentifier(
                                $activityAdminColumn
                            );


                        $activityInsertValues[] =
                            ':activity_admin';


                        $activityParams[
                            ':activity_admin'
                        ] =
                            $adminId;
                    }
                }


                if (
                    !empty(
                        $activityInsertColumns
                    )
                ) {

                    $activitySQL =
                        "
                        INSERT INTO activity_logs
                        (
                            " .
                        implode(
                            ', ',
                            $activityInsertColumns
                        ) .
                        "
                        )
                        VALUES
                        (
                            " .
                        implode(
                            ', ',
                            $activityInsertValues
                        ) .
                        "
                        )
                        ";


                    $activityStmt =
                        $pdo->prepare(
                            $activitySQL
                        );


                    $activityStmt->execute(
                        $activityParams
                    );
                }
            }

        } catch (Throwable $activityException) {

            /**
             * Activity logging must never prevent the
             * About page from saving successfully.
             */
        }


        /**
         * -------------------------------------------------
         * SUCCESS
         * -------------------------------------------------
         */

        $_SESSION[
            'about_success'
        ] =
            'About details updated successfully.';


        /**
         * -------------------------------------------------
         * REDIRECT
         * -------------------------------------------------
         *
         * Prevent duplicate form submission.
         */

        header(
            'Location: about-details.php'
        );

        exit;

    } catch (Throwable $e) {

        $aboutError =
            $e->getMessage();

        /**
         * Preserve entered values so user does not lose
         * form data after validation error.
         */

        $aboutData['title'] =
            $newTitle ??
            $aboutData['title'];


        $aboutData['subtitle'] =
            $newSubtitle ??
            $aboutData['subtitle'];


        $aboutData['content'] =
            $newContent ??
            $aboutData['content'];


        $aboutData['instagram'] =
            $newInstagram ??
            $aboutData['instagram'];


        $aboutData['twitter'] =
            $newTwitter ??
            $aboutData['twitter'];


        $aboutData['facebook'] =
            $newFacebook ??
            $aboutData['facebook'];
    }
}


/**
 * =========================================================
 * CSRF TOKEN
 * =========================================================
 */

if (
    !isset(
        $_SESSION['about_csrf_token']
    )
) {

    $_SESSION[
        'about_csrf_token'
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION[
        'about_csrf_token'
    ];


/**
 * =========================================================
 * IMAGE URL
 * =========================================================
 */

$imageUrl =
    '';


if (
    trim(
        (string)
        $aboutData['image']
    ) !== ''
) {

    $storedImage =
        trim(
            (string)
            $aboutData['image']
        );


    /**
     * If database stores a full URL,
     * use it directly.
     */

    if (
        filter_var(
            $storedImage,
            FILTER_VALIDATE_URL
        )
    ) {

        $imageUrl =
            $storedImage;

    } else {

        /**
         * Normalize local path.
         */

        $storedImage =
            ltrim(
                str_replace(
                    '\\',
                    '/',
                    $storedImage
                ),
                '/'
            );


        if (
            strpos(
                $storedImage,
                'assets/'
            ) === 0
        ) {

            $imageUrl =
                '../' .
                $storedImage;

        } elseif (
            strpos(
                $storedImage,
                '../'
            ) === 0
        ) {

            $imageUrl =
                $storedImage;

        } else {

            $imageUrl =
                '../assets/images/' .
                $storedImage;
        }
    }
}


/**
 * =========================================================
 * CHARACTER COUNTS
 * =========================================================
 */

$titleLength =
    strlen(
        (string)
        $aboutData['title']
    );


$subtitleLength =
    strlen(
        (string)
        $aboutData['subtitle']
    );


$contentLength =
    strlen(
        (string)
        $aboutData['content']
    );


/**
 * =========================================================
 * PAGE
 * =========================================================
 */

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Ssrini Handcrafts Admin About Details"
    >

    <title>
        <?= e($pageTitle) ?>
        | Ssrini Handcrafts
    </title>


    <!-- =====================================================
         EXISTING ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         ABOUT PAGE STYLES
         ===================================================== -->

    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .about-page {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        /* =====================================================
           ALERTS
           ===================================================== */

        .about-alert {

            display: flex;

            align-items: flex-start;

            gap: 11px;

            padding:
                13px 15px;

            border-radius: 11px;

            font-size: 12px;

            line-height: 1.5;

        }


        .about-alert-success {

            background: #edf9f1;

            border:
                1px solid #ccebd6;

            color: #267346;

        }


        .about-alert-error {

            background: #fff2f2;

            border:
                1px solid #ffd3d3;

            color: #c53030;

        }


        .about-alert-warning {

            background: #fff8e9;

            border:
                1px solid #f4dfae;

            color: #956700;

        }


        .about-alert-icon {

            font-size: 16px;

            line-height: 1;

        }


        /* =====================================================
           MAIN GRID
           ===================================================== */

        .about-main-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(330px, 0.8fr);

            gap: 20px;

            align-items: start;

        }


        /* =====================================================
           CARD
           ===================================================== */

        .about-card {

            background: #ffffff;

            border:
                1px solid #eeeaf2;

            border-radius: 16px;

            box-shadow:
                0 7px 22px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );

            overflow: hidden;

        }


        .about-card-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .about-card-title {

            color: #25212d;

            font-size: 15px;

            font-weight: 700;

        }


        .about-card-description {

            margin-top: 4px;

            color: #9a94a2;

            font-size: 11px;

            line-height: 1.5;

        }


        .about-card-body {

            padding:
                20px;

        }


        /* =====================================================
           FORM
           ===================================================== */

        .about-form {

            display: flex;

            flex-direction: column;

            gap: 18px;

        }


        .about-form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .about-form-label {

            color: #5f5865;

            font-size: 11px;

            font-weight: 700;

        }


        .about-required {

            color: #c53030;

        }


        .about-input,

        .about-textarea {

            width: 100%;

            border:
                1px solid #e3dfea;

            border-radius: 10px;

            background: #ffffff;

            color: #25212d;

            padding:
                11px 12px;

            outline: none;

            font-family: inherit;

            font-size: 12px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

            box-sizing: border-box;

        }


        .about-input {

            height: 43px;

        }


        .about-textarea {

            min-height: 240px;

            resize: vertical;

            line-height: 1.6;

        }


        .about-input:focus,

        .about-textarea:focus {

            border-color: #9b48d1;

            box-shadow:
                0 0 0 3px
                rgba(
                    155,
                    72,
                    209,
                    0.10
                );

        }


        .about-input::placeholder,

        .about-textarea::placeholder {

            color: #b1abb7;

        }


        .about-form-help {

            color: #a19aa8;

            font-size: 10px;

            line-height: 1.5;

        }


        .about-character-count {

            text-align: right;

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           TWO COLUMN FORM
           ===================================================== */

        .about-form-row {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 14px;

        }


        /* =====================================================
           SOCIAL LINKS
           ===================================================== */

        .about-social-section {

            padding-top: 4px;

            border-top:
                1px solid #eeeaf2;

        }


        .about-social-heading {

            margin-bottom: 14px;

            color: #403a47;

            font-size: 12px;

            font-weight: 700;

        }


        .about-social-item {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 12px;

        }


        .about-social-icon {

            width: 34px;

            height: 34px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #f5f1f8;

            color: #7627c9;

            font-size: 12px;

            font-weight: 800;

        }


        .about-social-field {

            flex: 1;

        }


        /* =====================================================
           IMAGE UPLOAD
           ===================================================== */

        .about-upload-box {

            border:
                1px dashed #d8cde1;

            border-radius: 13px;

            padding: 15px;

            background: #fcfbfd;

        }


        .about-current-image {

            width: 100%;

            min-height: 160px;

            max-height: 250px;

            object-fit: contain;

            border-radius: 10px;

            border:
                1px solid #eeeaf2;

            background: #faf9fc;

            display: block;

            margin-bottom: 12px;

        }


        .about-no-image {

            min-height: 160px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            border:
                1px solid #eeeaf2;

            border-radius: 10px;

            background: #faf9fc;

            color: #aaa3b0;

            margin-bottom: 12px;

        }


        .about-no-image-icon {

            font-size: 30px;

            margin-bottom: 8px;

        }


        .about-file-input {

            width: 100%;

            font-size: 11px;

            color: #6e6676;

        }


        .about-file-input::file-selector-button {

            border: none;

            border-radius: 8px;

            padding:
                8px 12px;

            margin-right: 8px;

            background: #f1e8ff;

            color: #7627c9;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;

        }


        .about-upload-help {

            margin-top: 8px;

            color: #a19aa8;

            font-size: 9px;

            line-height: 1.5;

        }


        /* =====================================================
           BUTTONS
           ===================================================== */

        .about-form-actions {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 9px;

            padding-top: 4px;

        }


        .about-save-button {

            min-height: 42px;

            border: none;

            border-radius: 10px;

            padding:
                0 19px;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 7px 16px
                rgba(
                    118,
                    39,
                    201,
                    0.20
                );

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

        }


        .about-save-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 10px 20px
                rgba(
                    118,
                    39,
                    201,
                    0.25
                );

        }


        .about-reset-button {

            min-height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0 17px;

            border:
                1px solid #e3dfea;

            border-radius: 10px;

            background: #ffffff;

            color: #6e6676;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

        }


        .about-reset-button:hover {

            background: #faf8fc;

            color: #7627c9;

        }


        /* =====================================================
           PREVIEW CARD
           ===================================================== */

        .about-preview {

            position: sticky;

            top: 20px;

        }


        .about-preview-cover {

            position: relative;

            min-height: 180px;

            padding: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #f7efff,
                    #fff4fa
                );

            overflow: hidden;

        }


        .about-preview-cover::before {

            content: '';

            position: absolute;

            width: 170px;

            height: 170px;

            border-radius: 50%;

            background:
                rgba(
                    118,
                    39,
                    201,
                    0.08
                );

            top: -80px;

            right: -50px;

        }


        .about-preview-image {

            position: relative;

            z-index: 2;

            max-width: 180px;

            max-height: 130px;

            object-fit: contain;

            border-radius: 10px;

        }


        .about-preview-placeholder {

            position: relative;

            z-index: 2;

            width: 80px;

            height: 80px;

            border-radius: 22px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ffffff;

            color: #7627c9;

            font-size: 30px;

            box-shadow:
                0 10px 25px
                rgba(
                    118,
                    39,
                    201,
                    0.10
                );

        }


        .about-preview-content {

            padding:
                20px;

        }


        .about-preview-label {

            color: #9a94a2;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 0.06em;

            text-transform: uppercase;

            margin-bottom: 7px;

        }


        .about-preview-title {

            color: #25212d;

            font-size: 19px;

            line-height: 1.25;

            font-weight: 800;

        }


        .about-preview-subtitle {

            margin-top: 7px;

            color: #7627c9;

            font-size: 11px;

            font-weight: 600;

            line-height: 1.5;

        }


        .about-preview-text {

            margin-top: 13px;

            color: #6e6676;

            font-size: 11px;

            line-height: 1.65;

            white-space: pre-line;

            display: -webkit-box;

            -webkit-line-clamp: 8;

            -webkit-box-orient: vertical;

            overflow: hidden;

        }


        /* =====================================================
           SOCIAL PREVIEW
           ===================================================== */

        .about-preview-social {

            display: flex;

            gap: 7px;

            margin-top: 16px;

            padding-top: 14px;

            border-top:
                1px solid #eeeaf2;

        }


        .about-preview-social a {

            width: 31px;

            height: 31px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #f5f1f8;

            color: #7627c9;

            text-decoration: none;

            font-size: 10px;

            font-weight: 700;

        }


        /* =====================================================
           INFORMATION BOX
           ===================================================== */

        .about-info-box {

            margin-top: 16px;

            padding:
                13px;

            border:
                1px solid #eeeaf2;

            border-radius: 11px;

            background: #fcfbfd;

        }


        .about-info-title {

            color: #403a47;

            font-size: 11px;

            font-weight: 700;

            margin-bottom: 5px;

        }


        .about-info-text {

            color: #9a94a2;

            font-size: 10px;

            line-height: 1.55;

        }


        /* =====================================================
           DATABASE STATUS
           ===================================================== */

        .about-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                5px 9px;

            border-radius: 20px;

            background: #edf9f1;

            color: #267346;

            font-size: 9px;

            font-weight: 700;

        }


        .about-status-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: #3aa568;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1100px) {

            .about-main-grid {

                grid-template-columns:
                    1fr;

            }


            .about-preview {

                position: static;

            }

        }


        @media (max-width: 700px) {

            .about-form-row {

                grid-template-columns:
                    1fr;

            }


            .about-card-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .about-form-actions {

                flex-direction: column-reverse;

                align-items: stretch;

            }


            .about-save-button,

            .about-reset-button {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <!-- =================================================
         SIDEBAR
         ================================================= -->

    <?php

    require_once __DIR__ .
        '/../includes/sidebar.php';

    ?>


    <!-- =================================================
         MAIN AREA
         ================================================= -->

    <main class="main-area">


        <!-- =================================================
             HEADER
             ================================================= -->

        <?php

        require_once __DIR__ .
            '/../includes/header.php';

        ?>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <div class="page-content">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <section class="page-header">


                <div>

                    <h1 class="page-title">

                        About Details

                    </h1>


                    <p class="page-description">

                        Manage the brand story, About page
                        image and social media information.

                    </p>

                </div>


                <div>

                    <?php if (
                        $aboutTableExists
                    ): ?>

                        <span
                            class="about-status"
                        >

                            <span
                                class="about-status-dot"
                            ></span>

                            Database Connected

                        </span>

                    <?php endif; ?>

                </div>


            </section>


            <!-- =================================================
                 ABOUT PAGE
                 ================================================= -->

            <div class="about-page">


                <!-- =================================================
                     SUCCESS MESSAGE
                     ================================================= -->

                <?php if (
                    $successMessage !== null
                ): ?>

                    <div
                        class="
                            about-alert
                            about-alert-success
                        "
                    >

                        <div
                            class="about-alert-icon"
                        >

                            ✓

                        </div>


                        <div>

                            <?= e(
                                $successMessage
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     ERROR MESSAGE
                     ================================================= -->

                <?php if (
                    $aboutError !== null
                ): ?>

                    <div
                        class="
                            about-alert
                            about-alert-error
                        "
                    >

                        <div
                            class="about-alert-icon"
                        >

                            !

                        </div>


                        <div>

                            <?= e(
                                $aboutError
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     WARNING MESSAGE
                     ================================================= -->

                <?php if (
                    $warningMessage !== null
                ): ?>

                    <div
                        class="
                            about-alert
                            about-alert-warning
                        "
                    >

                        <div
                            class="about-alert-icon"
                        >

                            ⚠

                        </div>


                        <div>

                            <?= e(
                                $warningMessage
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <?php if (
                    $aboutTableExists
                ): ?>


                    <!-- =================================================
                         MAIN GRID
                         ================================================= -->

                    <div
                        class="about-main-grid"
                    >


                        <!-- =================================================
                             EDIT CARD
                             ================================================= -->

                        <section
                            class="about-card"
                        >


                            <div
                                class="about-card-header"
                            >

                                <div>

                                    <div
                                        class="about-card-title"
                                    >

                                        About Page Content

                                    </div>


                                    <div
                                        class="about-card-description"
                                    >

                                        Update the information
                                        displayed on your public
                                        About page.

                                    </div>

                                </div>


                                <?php if (
                                    $recordId !== null
                                ): ?>

                                    <span
                                        style="
                                            color:#aaa3b0;
                                            font-size:10px;
                                        "
                                    >

                                        Record #

                                        <?= e(
                                            $recordId
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <div
                                class="about-card-body"
                            >


                                <form
                                    method="POST"
                                    action="about-details.php"
                                    enctype="multipart/form-data"
                                    class="about-form"
                                    id="aboutForm"
                                >


                                    <!-- =================================================
                                         CSRF
                                         ================================================= -->

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <!-- =================================================
                                         TITLE + SUBTITLE
                                         ================================================= -->

                                    <div
                                        class="about-form-row"
                                    >


                                        <!-- TITLE -->

                                        <div
                                            class="about-form-group"
                                        >

                                            <label
                                                class="about-form-label"
                                                for="aboutTitle"
                                            >

                                                About Title

                                                <span
                                                    class="about-required"
                                                >
                                                    *
                                                </span>

                                            </label>


                                            <input
                                                type="text"
                                                id="aboutTitle"
                                                name="title"
                                                class="about-input"
                                                maxlength="150"
                                                value="<?= e(
                                                    $aboutData['title']
                                                ) ?>"
                                                placeholder="Enter About page title"
                                                required
                                            >


                                            <div
                                                class="about-character-count"
                                                id="titleCounter"
                                            >

                                                <?= e(
                                                    $titleLength
                                                ) ?>

                                                characters

                                            </div>

                                        </div>


                                        <!-- SUBTITLE -->

                                        <div
                                            class="about-form-group"
                                        >

                                            <label
                                                class="about-form-label"
                                                for="aboutSubtitle"
                                            >

                                                Subtitle / Tagline

                                            </label>


                                            <input
                                                type="text"
                                                id="aboutSubtitle"
                                                name="subtitle"
                                                class="about-input"
                                                maxlength="255"
                                                value="<?= e(
                                                    $aboutData['subtitle']
                                                ) ?>"
                                                placeholder="Enter short tagline"
                                            >


                                            <div
                                                class="about-character-count"
                                                id="subtitleCounter"
                                            >

                                                <?= e(
                                                    $subtitleLength
                                                ) ?>

                                                characters

                                            </div>

                                        </div>


                                    </div>


                                    <!-- =================================================
                                         STORY / DESCRIPTION
                                         ================================================= -->

                                    <div
                                        class="about-form-group"
                                    >

                                        <label
                                            class="about-form-label"
                                            for="aboutContent"
                                        >

                                            Brand Story / Description

                                            <span
                                                class="about-required"
                                            >
                                                *
                                            </span>

                                        </label>


                                        <textarea
                                            id="aboutContent"
                                            name="content"
                                            class="about-textarea"
                                            placeholder="Write your brand story..."
                                            required
                                        ><?= e(
                                            $aboutData['content']
                                        ) ?></textarea>


                                        <div
                                            class="about-character-count"
                                            id="contentCounter"
                                        >

                                            <?= e(
                                                $contentLength
                                            ) ?>

                                            characters

                                        </div>


                                        <div
                                            class="about-form-help"
                                        >

                                            You can use multiple
                                            paragraphs. Line breaks
                                            will be preserved on
                                            the preview.

                                        </div>

                                    </div>


                                    <!-- =================================================
                                         IMAGE
                                         ================================================= -->

                                    <div
                                        class="about-form-group"
                                    >

                                        <label
                                            class="about-form-label"
                                            for="aboutImage"
                                        >

                                            About Image / Brand Logo

                                        </label>


                                        <div
                                            class="about-upload-box"
                                        >


                                            <?php if (
                                                $imageUrl !== ''
                                            ): ?>

                                                <img
                                                    src="<?= e(
                                                        $imageUrl
                                                    ) ?>"
                                                    alt="Current About Image"
                                                    class="about-current-image"
                                                    id="currentAboutImage"
                                                >

                                            <?php else: ?>

                                                <div
                                                    class="about-no-image"
                                                    id="currentAboutImage"
                                                >

                                                    <div
                                                        class="about-no-image-icon"
                                                    >

                                                        🖼️

                                                    </div>


                                                    <div>

                                                        No image uploaded

                                                    </div>

                                                </div>

                                            <?php endif; ?>


                                            <input
                                                type="file"
                                                id="aboutImage"
                                                name="about_image"
                                                class="about-file-input"
                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                            >


                                            <div
                                                class="about-upload-help"
                                            >

                                                Recommended:
                                                JPG, PNG or WEBP.
                                                Maximum file size:
                                                5 MB.

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =================================================
                                         SOCIAL LINKS
                                         ================================================= -->

                                    <div
                                        class="about-social-section"
                                    >


                                        <div
                                            class="about-social-heading"
                                        >

                                            Social Media Links

                                        </div>


                                        <!-- INSTAGRAM -->

                                        <div
                                            class="about-social-item"
                                        >

                                            <div
                                                class="about-social-icon"
                                            >

                                                IG

                                            </div>


                                            <div
                                                class="about-social-field"
                                            >

                                                <input
                                                    type="url"
                                                    name="instagram"
                                                    class="about-input"
                                                    value="<?= e(
                                                        $aboutData['instagram']
                                                    ) ?>"
                                                    placeholder="https://www.instagram.com/..."
                                                >

                                            </div>

                                        </div>


                                        <!-- X -->

                                        <div
                                            class="about-social-item"
                                        >

                                            <div
                                                class="about-social-icon"
                                            >

                                                X

                                            </div>


                                            <div
                                                class="about-social-field"
                                            >

                                                <input
                                                    type="url"
                                                    name="twitter"
                                                    class="about-input"
                                                    value="<?= e(
                                                        $aboutData['twitter']
                                                    ) ?>"
                                                    placeholder="https://x.com/..."
                                                >

                                            </div>

                                        </div>


                                        <!-- FACEBOOK -->

                                        <div
                                            class="about-social-item"
                                        >

                                            <div
                                                class="about-social-icon"
                                            >

                                                FB

                                            </div>


                                            <div
                                                class="about-social-field"
                                            >

                                                <input
                                                    type="url"
                                                    name="facebook"
                                                    class="about-input"
                                                    value="<?= e(
                                                        $aboutData['facebook']
                                                    ) ?>"
                                                    placeholder="https://www.facebook.com/..."
                                                >

                                            </div>

                                        </div>


                                    </div>


                                    <!-- =================================================
                                         FORM ACTIONS
                                         ================================================= -->

                                    <div
                                        class="about-form-actions"
                                    >


                                        <a
                                            href="about-details.php"
                                            class="about-reset-button"
                                        >

                                            Reset

                                        </a>


                                        <button
                                            type="submit"
                                            class="about-save-button"
                                            id="saveAboutButton"
                                        >

                                            💾

                                            Save About Details

                                        </button>


                                    </div>


                                </form>


                            </div>


                        </section>


                        <!-- =================================================
                             PREVIEW CARD
                             ================================================= -->

                        <aside
                            class="
                                about-card
                                about-preview
                            "
                        >


                            <div
                                class="about-card-header"
                            >

                                <div>

                                    <div
                                        class="about-card-title"
                                    >

                                        Live Preview

                                    </div>


                                    <div
                                        class="about-card-description"
                                    >

                                        Preview of your About
                                        content.

                                    </div>

                                </div>

                            </div>


                            <!-- PREVIEW IMAGE -->

                            <div
                                class="about-preview-cover"
                                id="previewCover"
                            >

                                <?php if (
                                    $imageUrl !== ''
                                ): ?>

                                    <img
                                        src="<?= e(
                                            $imageUrl
                                        ) ?>"
                                        alt="Ssrini"
                                        class="about-preview-image"
                                        id="previewImage"
                                    >

                                <?php else: ?>

                                    <div
                                        class="about-preview-placeholder"
                                        id="previewPlaceholder"
                                    >

                                        S

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- PREVIEW CONTENT -->

                            <div
                                class="about-preview-content"
                            >


                                <div
                                    class="about-preview-label"
                                >

                                    About the Brand

                                </div>


                                <div
                                    class="about-preview-title"
                                    id="previewTitle"
                                >

                                    <?= e(
                                        $aboutData['title']
                                    ) ?>

                                </div>


                                <div
                                    class="about-preview-subtitle"
                                    id="previewSubtitle"
                                >

                                    <?= e(
                                        $aboutData['subtitle']
                                    ) ?>

                                </div>


                                <div
                                    class="about-preview-text"
                                    id="previewContent"
                                >

                                    <?= e(
                                        $aboutData['content']
                                    ) ?>

                                </div>


                                <!-- =================================================
                                     SOCIAL PREVIEW
                                     ================================================= -->

                                <div
                                    class="about-preview-social"
                                >


                                    <?php if (
                                        trim(
                                            $aboutData['instagram']
                                        ) !== ''
                                    ): ?>

                                        <a
                                            href="<?= e(
                                                $aboutData['instagram']
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="Instagram"
                                        >

                                            IG

                                        </a>

                                    <?php endif; ?>


                                    <?php if (
                                        trim(
                                            $aboutData['twitter']
                                        ) !== ''
                                    ): ?>

                                        <a
                                            href="<?= e(
                                                $aboutData['twitter']
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="X"
                                        >

                                            X

                                        </a>

                                    <?php endif; ?>


                                    <?php if (
                                        trim(
                                            $aboutData['facebook']
                                        ) !== ''
                                    ): ?>

                                        <a
                                            href="<?= e(
                                                $aboutData['facebook']
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="Facebook"
                                        >

                                            FB

                                        </a>

                                    <?php endif; ?>


                                </div>


                                <!-- =================================================
                                     INFO BOX
                                     ================================================= -->

                                <div
                                    class="about-info-box"
                                >

                                    <div
                                        class="about-info-title"
                                    >

                                        Content Management

                                    </div>


                                    <div
                                        class="about-info-text"
                                    >

                                        Changes made here are
                                        stored in the
                                        <strong>
                                            about_content
                                        </strong>
                                        table and can be used
                                        by the frontend About page.

                                    </div>

                                </div>


                                <?php if (
                                    !empty(
                                        $aboutData['updated_at']
                                    )
                                ): ?>

                                    <div
                                        style="
                                            margin-top:12px;
                                            color:#aaa3b0;
                                            font-size:9px;
                                        "
                                    >

                                        Last updated:

                                        <?= e(
                                            formatDateTime(
                                                $aboutData['updated_at']
                                            )
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </div>


                        </aside>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         TABLE NOT AVAILABLE
                         ================================================= -->

                    <section
                        class="about-card"
                    >

                        <div
                            class="about-card-body"
                            style="
                                padding:65px 20px;
                                text-align:center;
                            "
                        >

                            <div
                                style="
                                    width:62px;
                                    height:62px;
                                    margin:0 auto 14px;
                                    border-radius:17px;
                                    background:#f1e8ff;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    font-size:28px;
                                "
                            >

                                📄

                            </div>


                            <div
                                style="
                                    color:#25212d;
                                    font-size:15px;
                                    font-weight:700;
                                "
                            >

                                About Content Table Not Available

                            </div>


                            <div
                                style="
                                    max-width:500px;
                                    margin:7px auto 0;
                                    color:#9a94a2;
                                    font-size:11px;
                                    line-height:1.6;
                                "
                            >

                                The
                                <strong>
                                    about_content
                                </strong>
                                table was not found in the
                                current database.

                                Please verify that the correct
                                database is connected in
                                <strong>
                                    config/database.php
                                </strong>.

                            </div>

                        </div>

                    </section>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     EXISTING ADMIN JAVASCRIPT
     ===================================================== -->

<script
    src="../assets/js/admin.js"
></script>


<!-- =====================================================
     ABOUT DETAILS JAVASCRIPT
     ===================================================== -->

<script>

    /*
    |--------------------------------------------------------------------------
    | FORM ELEMENTS
    |--------------------------------------------------------------------------
    */

    const aboutForm =
        document.getElementById(
            'aboutForm'
        );


    const aboutTitle =
        document.getElementById(
            'aboutTitle'
        );


    const aboutSubtitle =
        document.getElementById(
            'aboutSubtitle'
        );


    const aboutContent =
        document.getElementById(
            'aboutContent'
        );


    const aboutImage =
        document.getElementById(
            'aboutImage'
        );


    const previewTitle =
        document.getElementById(
            'previewTitle'
        );


    const previewSubtitle =
        document.getElementById(
            'previewSubtitle'
        );


    const previewContent =
        document.getElementById(
            'previewContent'
        );


    const titleCounter =
        document.getElementById(
            'titleCounter'
        );


    const subtitleCounter =
        document.getElementById(
            'subtitleCounter'
        );


    const contentCounter =
        document.getElementById(
            'contentCounter'
        );


    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTERS
    |--------------------------------------------------------------------------
    */

    function updateCharacterCounter(
        input,
        counter
    ) {

        if (
            !input ||
            !counter
        ) {

            return;
        }


        counter.textContent =
            input.value.length +
            ' characters';

    }


    /*
    |--------------------------------------------------------------------------
    | LIVE TITLE PREVIEW
    |--------------------------------------------------------------------------
    */

    if (
        aboutTitle &&
        previewTitle
    ) {

        aboutTitle.addEventListener(
            'input',
            function () {

                previewTitle.textContent =
                    this.value ||
                    'About Ssrini';


                updateCharacterCounter(
                    aboutTitle,
                    titleCounter
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LIVE SUBTITLE PREVIEW
    |--------------------------------------------------------------------------
    */

    if (
        aboutSubtitle &&
        previewSubtitle
    ) {

        aboutSubtitle.addEventListener(
            'input',
            function () {

                previewSubtitle.textContent =
                    this.value ||
                    'Creative handcrafted products from Bengal.';


                updateCharacterCounter(
                    aboutSubtitle,
                    subtitleCounter
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LIVE CONTENT PREVIEW
    |--------------------------------------------------------------------------
    */

    if (
        aboutContent &&
        previewContent
    ) {

        aboutContent.addEventListener(
            'input',
            function () {

                previewContent.textContent =
                    this.value ||
                    'Your brand story will appear here.';


                updateCharacterCounter(
                    aboutContent,
                    contentCounter
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE PREVIEW
    |--------------------------------------------------------------------------
    */

    if (
        aboutImage
    ) {

        aboutImage.addEventListener(
            'change',
            function () {

                const file =
                    this.files &&
                    this.files[0];


                if (
                    !file
                ) {

                    return;
                }


                const allowedTypes = [

                    'image/jpeg',

                    'image/png',

                    'image/webp'

                ];


                if (
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    alert(
                        'Please select a JPG, PNG or WEBP image.'
                    );


                    this.value =
                        '';

                    return;
                }


                const maxSize =
                    5 *
                    1024 *
                    1024;


                if (
                    file.size >
                    maxSize
                ) {

                    alert(
                        'Image size must be 5 MB or less.'
                    );


                    this.value =
                        '';

                    return;
                }


                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        const previewCover =
                            document.getElementById(
                                'previewCover'
                            );


                        if (
                            !previewCover
                        ) {

                            return;
                        }


                        let previewImage =
                            document.getElementById(
                                'previewImage'
                            );


                        const previewPlaceholder =
                            document.getElementById(
                                'previewPlaceholder'
                            );


                        if (
                            previewImage
                        ) {

                            previewImage.src =
                                event.target.result;

                        } else {

                            previewImage =
                                document.createElement(
                                    'img'
                                );


                            previewImage.id =
                                'previewImage';


                            previewImage.className =
                                'about-preview-image';


                            previewImage.alt =
                                'About Preview';


                            previewCover.innerHTML =
                                '';


                            previewCover.appendChild(
                                previewImage
                            );


                            previewImage.src =
                                event.target.result;
                        }


                        if (
                            previewPlaceholder
                        ) {

                            previewPlaceholder.remove();

                        }

                    };


                reader.readAsDataURL(
                    file
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION PROTECTION
    |--------------------------------------------------------------------------
    */

    if (
        aboutForm
    ) {

        aboutForm.addEventListener(
            'submit',
            function (event) {

                const title =
                    aboutTitle
                        ? aboutTitle.value.trim()
                        : '';


                const content =
                    aboutContent
                        ? aboutContent.value.trim()
                        : '';


                if (
                    title === ''
                ) {

                    event.preventDefault();


                    alert(
                        'Please enter the About page title.'
                    );


                    if (
                        aboutTitle
                    ) {

                        aboutTitle.focus();

                    }


                    return;
                }


                if (
                    content === ''
                ) {

                    event.preventDefault();


                    alert(
                        'Please enter the brand story or description.'
                    );


                    if (
                        aboutContent
                    ) {

                        aboutContent.focus();

                    }


                    return;
                }


                const saveButton =
                    document.getElementById(
                        'saveAboutButton'
                    );


                if (
                    saveButton
                ) {

                    saveButton.disabled =
                        true;


                    saveButton.innerHTML =
                        '⏳ Saving...';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INITIAL CHARACTER COUNTERS
    |--------------------------------------------------------------------------
    */

    updateCharacterCounter(
        aboutTitle,
        titleCounter
    );


    updateCharacterCounter(
        aboutSubtitle,
        subtitleCounter
    );


    updateCharacterCounter(
        aboutContent,
        contentCounter
    );


</script>


</body>

</html>