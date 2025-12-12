buildscript {
    repositories {
        google()
        mavenCentral()
    }
}

allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

val newBuildDir: Directory =
    rootProject.layout.buildDirectory
        .dir("../../build")
        .get()
rootProject.layout.buildDirectory.value(newBuildDir)

subprojects {
    val newSubprojectBuildDir: Directory = newBuildDir.dir(project.name)
    project.layout.buildDirectory.value(newSubprojectBuildDir)
}
subprojects {
    project.evaluationDependsOn(":app")

    // Some older Flutter plugins (e.g., flutter_app_badger) hardcode very low
    // compileSdkVersion, which breaks resource linking on modern Android.
    // Force all Android subprojects to use the app's compileSdk.
    fun forceCompileSdk(androidExt: Any?) {
        if (androidExt == null) return
        val targetSdk = 36
        val targetSdkString = targetSdk.toString()

        // Try multiple setter shapes to support old/new AGP.
        val candidates: List<Pair<String, Any>> = listOf(
            "setCompileSdkVersion" to targetSdk,
            "setCompileSdkVersion" to targetSdkString,
            "setCompileSdk" to targetSdk,
            "setCompileSdk" to targetSdkString,
        )

        for ((method, arg) in candidates) {
            try {
                androidExt.javaClass.methods
                    .firstOrNull { it.name == method && it.parameterTypes.size == 1 }
                    ?.invoke(androidExt, arg)
                return
            } catch (_: Throwable) {
                // keep trying
            }
        }
    }

    fun applyForce() = forceCompileSdk(extensions.findByName("android"))

    pluginManager.withPlugin("com.android.library") { applyForce() }
    pluginManager.withPlugin("com.android.application") { applyForce() }
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
