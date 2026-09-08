#define _GNU_SOURCE
#include <dlfcn.h>
#include <fcntl.h>
#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <unistd.h>

static int synchronized = 0;

static void synchronize_after_success(const char *wrapper, const char *path, int result)
{
    const char *disabled = getenv("FMONITOR_TEST_CSS_SWAP_DISABLE_ABI");
    if (result != 0 || synchronized || (disabled && strcmp(disabled, wrapper) == 0)) return;
    const char *target = getenv("FMONITOR_TEST_CSS_SWAP_PATH");
    const char *ready = getenv("FMONITOR_TEST_CSS_SWAP_READY");
    const char *release = getenv("FMONITOR_TEST_CSS_SWAP_RELEASE");
    if (!target || !ready || !release || strcmp(path, target) != 0) return;
    synchronized = 1;
    int marker = open(ready, O_WRONLY | O_CREAT | O_EXCL, 0600);
    if (marker >= 0) close(marker);
    for (int attempt = 0; attempt < 5000 && access(release, F_OK) != 0; ++attempt) usleep(1000);
}

static int intercepted_lstat(const char *path, struct stat *buffer)
{
#if defined(__APPLE__)
    int result = fstatat(AT_FDCWD, path, buffer, AT_SYMLINK_NOFOLLOW);
#else
    static int (*real_lstat)(const char *, struct stat *) = NULL;
    if (real_lstat == NULL) real_lstat = dlsym(RTLD_NEXT, "lstat");
    if (real_lstat == NULL) { errno = ENOSYS; return -1; }
    int result = real_lstat(path, buffer);
#endif
    synchronize_after_success("lstat", path, result);
    return result;
}

#if defined(__APPLE__)
#define DYLD_INTERPOSE(replacement, replacee) \
    __attribute__((used)) static struct { const void *replacement; const void *replacee; } \
    interpose_##replacee __attribute__((section("__DATA,__interpose"))) = { \
        (const void *)(unsigned long)&replacement, (const void *)(unsigned long)&replacee \
    }
DYLD_INTERPOSE(intercepted_lstat, lstat);
#else
int lstat(const char *path, struct stat *buffer)
{
    return intercepted_lstat(path, buffer);
}
#endif

#if defined(__linux__) && defined(__GLIBC__)
int lstat64(const char *path, struct stat64 *buffer)
{
    static int (*real_lstat64)(const char *, struct stat64 *) = NULL;
    if (real_lstat64 == NULL) real_lstat64 = dlsym(RTLD_NEXT, "lstat64");
    if (real_lstat64 == NULL) { errno = ENOSYS; return -1; }
    int result = real_lstat64(path, buffer);
    synchronize_after_success("lstat64", path, result);
    return result;
}
#endif
