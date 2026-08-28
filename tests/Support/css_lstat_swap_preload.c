#define _GNU_SOURCE
#include <dlfcn.h>
#include <fcntl.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <unistd.h>

int lstat(const char *path, struct stat *buffer)
{
    static int (*real_lstat)(const char *, struct stat *) = NULL;
    static int intercepted = 0;
    if (real_lstat == NULL) {
        real_lstat = dlsym(RTLD_NEXT, "lstat");
    }
    int result = real_lstat(path, buffer);
    const char *target = getenv("FMONITOR_TEST_CSS_SWAP_PATH");
    const char *ready = getenv("FMONITOR_TEST_CSS_SWAP_READY");
    const char *release = getenv("FMONITOR_TEST_CSS_SWAP_RELEASE");
    if (!intercepted && result == 0 && target && ready && release && strcmp(path, target) == 0) {
        intercepted = 1;
        int marker = open(ready, O_WRONLY | O_CREAT | O_EXCL, 0600);
        if (marker >= 0) close(marker);
        for (int attempt = 0; attempt < 5000 && access(release, F_OK) != 0; ++attempt) {
            usleep(1000);
        }
    }
    return result;
}
